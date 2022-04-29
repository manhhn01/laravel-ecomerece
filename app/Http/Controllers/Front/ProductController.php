<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Repositories\Products\ProductRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductController extends Controller
{
    protected $productRepo;
    public function __construct(ProductRepositoryInterface $productRepo)
    {
        $this->productRepo = $productRepo;
    }

    public function search(Request $request)
    {
        if (!empty($query = $request->query('q'))) {
            $products = Product::search($query)
                ->where('status', 1)
                ->orderBy('created_at', 'desc')
                ->paginate(30)
                ->tap(function ($products) {
                    $products
                        ->makeHidden(['reviews', 'publicReviews'])
                        ->load('variants')
                        ->loadCount('publicReviews')
                        ->append('options');
                });
        }

        return $products ?? (new LengthAwarePaginator([], 0, 30))->withPath($request->url());
    }

    public function show($id_slug)
    {
        $product = $this->productRepo->findByIdOrSlug($id_slug);
        if ($product->status == 1)
            return $product
                ->load('images', 'category:id,parent_id,slug,name', 'publicReviews.user', 'variants')
                ->loadCount('publicReviews')
                ->append('options');
        else
            throw new NotFoundHttpException('Product not found');
    }
}
