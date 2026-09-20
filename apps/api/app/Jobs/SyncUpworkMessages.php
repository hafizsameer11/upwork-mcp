<?php

namespace App\Jobs;

use App\Models\ClientMessage;
use App\Models\UpworkAccount;
use App\Services\Slack\SlackNotifier;
use App\Services\Upwork\UpworkMcpClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class SyncUpworkMessages implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $accountId) {}

    public function handle(SlackNotifier $slack): void
    {
        $account = UpworkAccount::query()->find($this->accountId);
        if (! $account) {
            return;
        }

        $client = new UpworkMcpClient($account);
        $result = $client->listMessages();
        $messages = $result['messages'] ?? $result['stories'] ?? $result['data'] ?? [];

        foreach ($messages as $msg) {
            $externalId = (string) ($msg['id'] ?? $msg['message_id'] ?? null);
            if (! $externalId) {
                continue;
            }

            $exists = ClientMessage::query()
                ->where('upwork_account_id', $account->id)
                ->where('message_id', $externalId)
                ->exists();

            $row = ClientMessage::query()->updateOrCreate(
                [
                    'upwork_account_id' => $account->id,
                    'message_id' => $externalId,
                ],
                [
                    'room_id' => $msg['room_id'] ?? null,
                    'direction' => ($msg['direction'] ?? 'inbound'),
                    'message_type' => $msg['type'] ?? 'message',
                    'body' => $msg['body'] ?? $msg['text'] ?? null,
                    'sent_at' => isset($msg['sent_at']) ? now()->parse($msg['sent_at']) : now(),
                    'raw_payload' => $msg,
                ]
            );

            if (! $exists && ($row->direction === 'inbound')) {
                $event = match ($row->message_type) {
                    'offer' => 'offer',
                    'invitation' => 'offer',
                    default => 'client_reply',
                };
                $slack->send($event, 'Upwork '.$row->message_type.': '.Str::limit($row->body ?? '', 120));
            }
        }
    }
}
