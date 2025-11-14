<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FAQ;
use App\Models\SupportTicket;
use App\Models\SupportMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Support",
 *     description="Customer support and FAQ endpoints"
 * )
 */
class SupportController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/support/faqs",
     *     tags={"Support"},
     *     summary="Get frequently asked questions",
     *     description="Returns a list of FAQs organized by category",
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         required=false,
     *         description="Filter by category",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of FAQs",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function faqs(Request $request)
    {
        $query = FAQ::where('is_published', true);

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        $faqs = $query->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $faqs
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/support/tickets",
     *     tags={"Support"},
     *     summary="Get support tickets",
     *     description="Returns user's support tickets",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Filter by status",
     *         @OA\Schema(type="string", enum={"open","in_progress","resolved","closed"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of support tickets",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function tickets(Request $request)
    {
        $query = SupportTicket::where('user_id', $request->user()->id);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $tickets = $query->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $tickets
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/support/tickets",
     *     tags={"Support"},
     *     summary="Create support ticket",
     *     description="Create a new support ticket",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"subject", "description", "priority", "category"},
     *             @OA\Property(property="subject", type="string", example="Issue with my order"),
     *             @OA\Property(property="description", type="string", example="I have a problem with order #123"),
     *             @OA\Property(property="priority", type="string", enum={"low","medium","high","urgent"}, example="medium"),
     *             @OA\Property(property="category", type="string", enum={"order","product","payment","account","other"}, example="order")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Support ticket created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Support ticket created successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function createTicket(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'category' => 'required|in:order,product,payment,account,other',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $ticket = SupportTicket::create([
            'user_id' => $request->user()->id,
            'ticket_number' => 'TKT-' . date('Y') . '-' . str_pad(SupportTicket::count() + 1, 6, '0', STR_PAD_LEFT),
            'subject' => $request->subject,
            'description' => $request->description,
            'priority' => $request->priority,
            'category' => $request->category,
            'status' => 'open',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Support ticket created successfully',
            'data' => $ticket
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/support/tickets/{id}",
     *     tags={"Support"},
     *     summary="Get support ticket details",
     *     description="Returns detailed information about a specific support ticket",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Support ticket ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Support ticket details",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Ticket not found")
     * )
     */
    public function ticketShow(Request $request, $id)
    {
        $ticket = SupportTicket::with(['messages' => function($query) {
            $query->orderBy('created_at', 'asc');
        }])
        ->where('user_id', $request->user()->id)
        ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $ticket
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/support/tickets/{id}/messages",
     *     tags={"Support"},
     *     summary="Add message to support ticket",
     *     description="Add a new message to an existing support ticket",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Support ticket ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"message"},
     *             @OA\Property(property="message", type="string", example="Thank you for your help!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Message added successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Message added successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Ticket not found"),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function addMessage(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $ticket = SupportTicket::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $message = SupportMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'message' => $request->message,
            'is_internal' => false,
        ]);

        // Update ticket status
        if ($ticket->status === 'resolved') {
            $ticket->update(['status' => 'open']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Message added successfully',
            'data' => $message
        ], 201);
    }
}