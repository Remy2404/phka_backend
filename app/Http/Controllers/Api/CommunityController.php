<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CommunityPost;
use App\Models\PostComment;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Community",
 *     description="Community posts and social features"
 * )
 */
class CommunityController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/community/posts",
     *     tags={"Community"},
     *     summary="Get community posts",
     *     description="Returns a list of community posts with pagination and filtering options",
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         required=false,
     *         description="Filter by category",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         required=false,
     *         description="Sort order",
     *         @OA\Schema(type="string", enum={"latest","popular","trending"}, default="latest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Paginated list of community posts",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function posts(Request $request)
    {
        $query = CommunityPost::with(['user:id,name,avatar'])
            ->where('is_published', true);

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Sorting
        switch ($request->get('sort', 'latest')) {
            case 'popular':
                $query->orderBy('likes_count', 'desc');
                break;
            case 'trending':
                $query->where('created_at', '>=', now()->subWeek())
                    ->orderBy('likes_count', 'desc')
                    ->orderBy('comments_count', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        $posts = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $posts
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/community/posts/{id}",
     *     tags={"Community"},
     *     summary="Get community post details",
     *     description="Returns detailed information about a specific community post including comments",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Community post ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Community post details",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Post not found")
     * )
     */
    public function postShow($id)
    {
        $post = CommunityPost::with([
            'user:id,name,avatar',
            'comments' => function($query) {
                $query->with('user:id,name,avatar')
                    ->orderBy('created_at', 'desc')
                    ->limit(10);
            }
        ])->where('is_published', true)
        ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $post
        ]);
    }
}