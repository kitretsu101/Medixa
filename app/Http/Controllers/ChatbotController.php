<?php

namespace App\Http\Controllers;

use App\Services\ChatService;
use App\Services\ChatSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    /**
     * Handle one chatbot message from a visitor or a signed-in user.
     *
     * The signed-in user is taken from the session. A presented chat session
     * is used only when this request owns it.
     */
    public function message(Request $request, ChatSessionService $sessions, ChatService $chat): JsonResponse
    {
        $rules = [
            'message' => ['required', 'string', 'max:2000'],
            'session_token' => ['nullable', 'string', 'max:36'],
            'locale' => ['nullable', 'string', 'in:en,bn'],
            'confirm' => ['nullable', 'array'],
        ];

        if (is_array($request->input('confirm'))) {
            $rules['confirm.doctor_id'] = ['required', 'integer'];
            $rules['confirm.appointment_date'] = ['required', 'date'];
            $rules['confirm.confirmed'] = ['required', 'accepted'];
        }

        $validated = $request->validate($rules);

        $session = $sessions->open($validated['session_token'] ?? null, $request->user());
        $confirmation = isset($validated['confirm'])
            ? [
                'doctor_id' => (int) $validated['confirm']['doctor_id'],
                'appointment_date' => (string) $validated['confirm']['appointment_date'],
            ]
            : null;

        return response()->json($chat->respond(
            message: $validated['message'],
            session: $session,
            locale: $validated['locale'] ?? 'en',
            user: $request->user(),
            confirmation: $confirmation,
        ));
    }

    /**
     * Delete one chat session identifier owned by this request.
     */
    public function destroy(Request $request, string $session, ChatSessionService $sessions): JsonResponse
    {
        $sessions->delete($session, $request->user());

        return response()->json([
            'deleted' => true,
        ]);
    }
}
