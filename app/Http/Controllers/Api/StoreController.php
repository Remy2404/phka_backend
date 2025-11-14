<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Stores",
 *     description="Store location and information endpoints"
 * )
 */
class StoreController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/stores",
     *     tags={"Stores"},
     *     summary="Get all stores",
     *     description="Returns a list of all active store locations",
     *     @OA\Parameter(
     *         name="city",
     *         in="query",
     *         required=false,
     *         description="Filter by city",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="state",
     *         in="query",
     *         required=false,
     *         description="Filter by state",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of stores",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Store::where('is_active', true);

        if ($request->has('city')) {
            $query->where('city', 'like', '%' . $request->city . '%');
        }

        if ($request->has('state')) {
            $query->where('state', 'like', '%' . $request->state . '%');
        }

        $stores = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $stores
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/stores/{id}",
     *     tags={"Stores"},
     *     summary="Get store details",
     *     description="Returns detailed information about a specific store",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Store ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Store details",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Store not found")
     * )
     */
    public function show($id)
    {
        $store = Store::where('is_active', true)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $store
        ]);
    }
}