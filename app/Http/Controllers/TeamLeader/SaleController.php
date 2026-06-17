<?php

namespace App\Http\Controllers\TeamLeader;

use App\Http\Controllers\Api\TeamLeaderCustomerNeedController;
use App\Http\Controllers\Api\TeamLeaderSaleApiController;
use App\Http\Controllers\Controller;
use App\Models\AgentCredit;
use App\Models\Branch;
use App\Models\Category;
use App\Models\CustomerNeed;
use App\Models\Product;
use App\Models\Setting;
use App\Models\PaymentOption;
use App\Models\TeamLeaderProductListAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function recordSale(): View
    {
        $teamLeaderId = (int) Auth::id();

        $availableProducts = TeamLeaderProductListAssignment::query()
            ->where('team_leader_id', $teamLeaderId)
            ->whereHas('productListItem', fn ($q) => $q->whereNull('sold_at'))
            ->with(['productListItem.product.category', 'productListItem.category'])
            ->get()
            ->map(function (TeamLeaderProductListAssignment $row) {
                $item = $row->productListItem;
                if (! $item) {
                    return null;
                }

                return [
                    'id' => $item->id,
                    'imei_number' => $item->imei_number,
                    'model' => $item->model,
                    'label' => trim(($item->category?->name ?? $item->product?->category?->name ?? '—') . ' – ' . ($item->model ?? $item->product?->name ?? 'Device') . ' (' . ($item->imei_number ?? '—') . ')'),
                ];
            })
            ->filter()
            ->values();

        $categories = Category::query()->whereHas('products')->orderBy('name')->get(['id', 'name']);
        $branches = Branch::query()->orderBy('name')->get(['id', 'name']);

        $watuChannel = $this->resolveWatuChannel();

        return view('team-leader.record-sale', [
            'availableProducts' => $availableProducts,
            'categories' => $categories,
            'branches' => $branches,
            'watuChannel' => $watuChannel,
        ]);
    }

    public function storeCreditSale(Request $request): RedirectResponse
    {
        $response = app(TeamLeaderSaleApiController::class)->sellCredit($request);
        $payload = json_decode($response->getContent(), true) ?? [];

        if ($response->getStatusCode() === 201) {
            return redirect()
                ->route('team-leader.credit-sales')
                ->with('success', $payload['message'] ?? 'Credit sale recorded.');
        }

        return back()
            ->withInput()
            ->withErrors(['sale' => $payload['message'] ?? 'Could not record credit sale.']);
    }

    public function creditSales(): View
    {
        $credits = AgentCredit::query()
            ->where('team_leader_id', Auth::id())
            ->with(['product.category', 'productListItem', 'paymentOption'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        return view('team-leader.credit-sales', compact('credits'));
    }

    public function leads(): View
    {
        $leads = CustomerNeed::query()
            ->where('team_leader_id', Auth::id())
            ->with(['category', 'product', 'branch'])
            ->latest('id')
            ->get();

        $categories = Category::query()->whereHas('products')->orderBy('name')->get(['id', 'name']);
        $branches = Branch::query()->orderBy('name')->get(['id', 'name']);

        return view('team-leader.leads', compact('leads', 'categories', 'branches'));
    }

    public function storeLead(Request $request): RedirectResponse
    {
        $response = app(TeamLeaderCustomerNeedController::class)->store($request);
        $payload = json_decode($response->getContent(), true) ?? [];

        if ($response->getStatusCode() === 201) {
            return redirect()
                ->route('team-leader.leads')
                ->with('success', $payload['message'] ?? 'Lead submitted.');
        }

        return back()
            ->withInput()
            ->withErrors(['lead' => $payload['message'] ?? 'Could not submit lead.']);
    }

    public function productsForCategory(int $category): \Illuminate\Http\JsonResponse
    {
        $products = Product::query()
            ->where('category_id', $category)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json(['data' => $products]);
    }

    private function resolveWatuChannel(): ?array
    {
        $allVisible = PaymentOption::visible()->orderBy('name')->get();
        $watuIdRaw = Setting::query()->where('key', 'default_watu_channel_id')->value('value');
        $watuId = is_numeric($watuIdRaw) ? (int) $watuIdRaw : null;
        $watuChannel = $watuId ? $allVisible->firstWhere('id', $watuId) : null;

        if (! $watuChannel) {
            $watuChannel = $allVisible->first(fn ($opt) => $opt->isWatuAgentCreditChannel());
        }

        return $watuChannel
            ? ['id' => $watuChannel->id, 'name' => $watuChannel->name]
            : null;
    }
}
