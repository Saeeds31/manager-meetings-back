<?php

namespace Modules\Wallet\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Wallet\Http\Requests\WalletStoreRequest;
use Modules\Wallet\Http\Requests\WalletUpdateRequest;
use Modules\Wallet\Models\Wallet;
use Modules\Wallet\Models\WalletTransaction;

class WalletController extends Controller
{
    public function userWallet(Request $request)
    {
        $user = $request->user();
        $transactions = WalletTransaction::where('wallet_id', $user->wallet->id)->get();
        return response()->json([
            'message' => "کیف پول کاربر",
            'success' => true,
            'wallet' => $user->wallet,
            'transactions' => $transactions
        ]);
    }
    /**
     * لیست کیف پول‌ها
     */
    public function index()
    {
        $wallets = Wallet::with(['user', 'transactions'])->paginate(20);
        return response()->json($wallets);
    }

    /**
     * ایجاد کیف پول جدید برای کاربر
     */
    public function store(WalletStoreRequest $request)
    {
        $data = $request->validated();

        $wallet = Wallet::create([
            'user_id' => $data['user_id'],
            'balance' => $data['balance'] ?? 0,
        ]);
        return response()->json([
            'message' => 'Wallet created successfully',
            'wallet' => $wallet->load(['user', 'transactions']),
        ], 201);
    }

    /**
     * نمایش جزئیات کیف پول
     */
    public function show(Wallet $wallet)
    {
        return response()->json($wallet->load(['user', 'transactions']));
    }

    /**
     * ویرایش کیف پول
     */
    public function update(WalletUpdateRequest $request, Wallet $wallet)
    {
        $data = $request->validated();

        $wallet->update($data);

        return response()->json([
            'message' => 'Wallet updated successfully',
            'wallet' => $wallet->load(['user', 'transactions']),
        ]);
    }

    /**
     * حذف کیف پول
     */
    public function destroy(Wallet $wallet)
    {
        $wallet->delete();

        return response()->json([
            'message' => 'Wallet deleted successfully',
        ]);
    }
}
