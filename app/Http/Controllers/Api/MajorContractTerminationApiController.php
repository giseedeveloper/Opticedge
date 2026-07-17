<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContractTerminationRequest;
use Illuminate\Http\Request;

/**
 * Major (team leader / regional manager) approval of contract terminations
 * has been removed. Vendor admin reviews and decides all exit requests.
 */
class MajorContractTerminationApiController extends Controller
{
    public function index(Request $request)
    {
        abort(403, 'Contract terminations are reviewed by vendor admin only.');
    }

    public function approve(Request $request, ContractTerminationRequest $contractTermination)
    {
        abort(403, 'Contract terminations are reviewed by vendor admin only.');
    }

    public function reject(Request $request, ContractTerminationRequest $contractTermination)
    {
        abort(403, 'Contract terminations are reviewed by vendor admin only.');
    }
}
