<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions\Functionstrait;

class MessageController extends Controller
{
    use Functionstrait;

    public function sendMessage(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'message' => 'nullable|string'
        ]);

        $targetUsername = $request->input('username');
        $messages = json_decode($this->getMessages());

        // Kullanıcı adı ile eşleşen chat_id'yi bul
        $chatId = null;
        foreach ($messages->result as $message) {
            // Mesajdaki kullanıcı adı
            $username = isset($message->message->from->username) ? $message->message->from->username : '';

            // Eğer kullanıcı adı eşleşiyorsa chat_id'yi al
            if ($username === $targetUsername) {
                $chatId = $message->message->chat->id;
                if ($message->message->text == "/start") {
                    $this->curl('sendMessage', ['chat_id' => $chatId, 'text' => 'Hi, your registration is complete.'], 'POST');
                }
                break;
            }
        }

        // Sonuçları döndür
        if ($chatId !== null) {
            if ($request->message) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Message sent successfully',
                    'chat_id' => $chatId
                ], 200);
            } else {
                return response()->json(['chat_id' => $chatId]);
            }
        } else {
            return response()->json(['error' => 'Username not found'], 404);
        }
    }

    public function getMessages()
    {
        // 'getUpdates' endpoint'ine istek yaparak yanıtı alın
        $response = $this->curl('getUpdates', null, 'GET');

        // Eğer 'response' bir JSON string ise, JSON decode ederek diziye çevirin
        // Eğer 'response' zaten bir dizi ise, bu adımı geçebilirsiniz.
        if (is_string($response)) {
            $responseData = json_decode($response, true);
        } else {
            $responseData = $response;
        }

        // Eğer 'ok' değeri true ise ve 'result' varsa
        if ($responseData['ok'] && isset($responseData['result'])) {
            // 'result' dizisini tersten sıralayın
            $responseData['result'] = array_reverse($responseData['result']);
        }

        // Güncellenmiş yanıtı JSON formatında encode ederek döndürün
        return json_encode($responseData);
    }

    public function getChats(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer'
        ]);
    }

    public function deleteChat($chat_id = null)
    {
        // API'yi çağırarak güncellemeleri alın
        $response = $this->curl('getUpdates', null, 'GET');

        // Sonuçları kontrol edin
        if (isset($response['result'])) {
            $messageIds = [];

            // Her güncelleme üzerinde döngü oluşturun
            foreach ($response['result'] as $update) {
                if (isset($update['message']['chat']['id']) && $update['message']['chat']['id'] == $chat_id) {
                    // Eşleşen chat_id'ye sahip mesajların message_id'sini ekleyin
                    $messageIds[] = $update['message']['message_id'];
                }
            }

            foreach ($messageIds as $messageId) {
                // Mesajları silmek için API'yi çağırın
                $test = $this->curl('deleteMessage', ['chat_id' => $chat_id, 'message_id' => $messageId], 'POST');
            }

            return response()->json(['message' => 'Chat deleted successfully']);
        }

        return [];
    }
}
