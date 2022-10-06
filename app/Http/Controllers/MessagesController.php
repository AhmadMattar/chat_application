<?php

namespace App\Http\Controllers;

use App\Events\MessageCreated;
use App\Models\Conversation;
use App\Models\Recipient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MessagesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($id)
    {
        $user = Auth::user();
        $conversations = $user->conversations()->with([
            'participants' => function ($builder) use ($user){
                $builder->where('user_id', '<>', $user->id);
            }
        ])->findOrFail($id);

        $messages = $conversations->messages()
                    ->with('user')
                    ->where(function($query) use($user){
                        $query->where('user_id', $user->id)
                            ->orwhereRaw('id IN (
                                SELECT message_id FROM recipients
                                WHERE recipients.message_id = messages.id
                                AND recipients.user_id = ?
                                AND recipients.deleted_at IS NULL
                            )', [$user->id]);
                    })
                    ->latest()
                    ->paginate();
        return [
            'conversations' => $conversations,
            'messages'      => $messages,
        ];
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // return $request->all();
        $request->validate([
            // 'message' => [
            //     Rule::requiredIf(function() use ($request){
            //         return !$request->hasFile('attachment');
            //     }),
            //     'string'
            // ],
            // 'attachment' => [
            //     Rule::requiredIf(function() use ($request){
            //         return !$request->has('message');
            //     }),
            //     'file'
            // ],
            'conversation_id' => [
                Rule::requiredIf(!$request->has('user_id')),
                'int',
                'exists:conversations,id'
            ],
            'user_id' => [
                Rule::requiredIf(!$request->has('conversation_id')),
                'int',
                'exists:users,id'
            ],
        ]);

        $conversation_id = $request->conversation_id;
        $user_id = $request->user_id;
        $user = Auth::user();

        DB::beginTransaction();
        try {
            //if the request have a conversation_id find the conversation
            if ($conversation_id)
            {
                $conversation = $user->conversations()->findOrFail($conversation_id);
            } else{ // if the request have a user_id, then get the conversation with this user if not found create new one
                $conversation = Conversation::whereType('peer')
                    ->whereHas('participants', function ($builder) use ($user, $user_id){
                       $builder->join('participants as participants2', 'participants.conversation_id', '=', 'participants2.conversation_id')
                       ->where('participants.user_id', '=', $user->id)
                       ->where('participants2.user_id', '=', $user_id);
                    })->first();
                // if their no conversation between two users before that
                if (!$conversation)
                {
                    $conversation = Conversation::create([
                       'user_id' => $user->id,
                       'type'    => 'peer',
                    ]);
                    //add participant to the conversation
                    $conversation->participants()->attach([
                       $user->id => ['joined_at'  => now()],
                       $user_id => ['joined_at'  => now()],
                    ]);
                }

            }

            $type = 'text';
            $message = $request->message;
            if($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $message = [
                    'file_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'file_path' => $file->store('attachments', [
                        'disk' => 'public'
                    ]),
                ];
                $type = 'attachment';
            }
            $message = $conversation->messages()->create([
               'user_id' => $user->id,
               'body'    => $message,
               'type'    => $type
            ]);

            //determine the recipients of this message
            DB::statement('
                    INSERT INTO recipients (user_id, message_id)
                    SELECT user_id, ? FROM participants
                    WHERE conversation_id = ?
                    AND user_id <> ?
                ',[$message->id, $conversation->id, $user->id]);
            $conversation->update([
                'last_message_id' => $message->id,
            ]);

            $message->load('user');
            DB::commit();

            //broadcast the message to send it in the channel
            broadcast(new MessageCreated($message));
        }catch (\Throwable $e){
            DB::rollBack();
            throw $e;
        }
        return $message;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Recipient::where([
            'user_id'       => Auth::id(),
            'message_id'    => $id,
        ])->delete();

        return [
            'message' => 'message deleted',
        ];
    }
}
