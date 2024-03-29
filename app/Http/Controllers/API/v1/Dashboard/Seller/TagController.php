<?php

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\Tag\StoreRequest;
use App\Http\Requests\Tag\UpdateRequest;
use App\Http\Resources\ShopTagResource;
use App\Http\Resources\TagResource;
use App\Models\Product;
use App\Models\Tag;
use App\Repositories\ShopTagRepository\ShopTagRepository;
use App\Repositories\TagRepository\TagRepository;
use App\Services\TagService\TagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TagController extends SellerBaseController
{
    private TagService $service;
    private TagRepository $repository;

    /**
     * @param TagService $service
     * @param TagRepository $repository
     */
    public function __construct(TagService $service, TagRepository $repository)
    {
        parent::__construct();

        $this->service = $service;
        $this->repository = $repository;
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function index(FilterParamsRequest $request)
    {
        if (!$this->shop) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_101]);
        }

        $tag = $this->repository->paginate($request->merge(['shop_id' => $this->shop->id])->all());

        return TagResource::collection($tag);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $product = Product::find($request->input('product_id'));

        if (!$this->shop || data_get($product, 'shop_id') !== data_get($this->shop, 'id')) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_101]);
        }

        $validated = $request->validated();

        $result = $this->service->create($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(__('web.record_successfully_created'), []);
    }

    /**
     * Display the specified resource.
     *
     * @param Tag $tag
     * @return JsonResponse
     */
    public function show(Tag $tag): JsonResponse
    {
        if (!$this->shop || data_get($tag, 'product.shop_id') !== data_get($this->shop, 'id')) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_101]);
        }

        return $this->successResponse(__('web.coupon_found'), TagResource::make($this->repository->show($tag)));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Tag $tag
     * @param UpdateRequest $request
     * @return JsonResponse
     */
    public function update(Tag $tag, UpdateRequest $request): JsonResponse
    {
        $product = Product::find($request->input('product_id'));

        if (!$this->shop || data_get($product, 'shop_id') !== data_get($this->shop, 'id')) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_101]);
        }

        $validated = $request->validated();

        $result = $this->service->update($tag, $validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(__('web.record_successfully_updated'), []);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function destroy(FilterParamsRequest $request): JsonResponse
    {
        if (!$this->shop) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_101]);
        }

        $this->service->delete($request->input('ids', []), $this->shop->id);

        return $this->successResponse(__('web.record_has_been_successfully_delete'));
    }

    public function shopTagsPaginate(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $models = (new ShopTagRepository)->paginate($request->all());

        return ShopTagResource::collection($models);
    }

}
