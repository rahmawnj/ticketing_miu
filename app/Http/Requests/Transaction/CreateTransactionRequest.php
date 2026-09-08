<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;

class CreateTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            // 'no_trx' => 'required|numeric',
            // 'name' => 'required|string',
            // 'name' => 'required|string',
            // 'ticket' => 'required|numeric',
            // 'type_customer' => 'required|string',
            // 'amount' => 'required|numeric',
            // 'print' => 'required|numeric',
            // 'harga_ticket' => 'required|numeric',
            // 'discount' => 'required|numeric',
            // 'metode' => 'required|string',
            // 'cash' => 'required|numeric',
            // 'kembalian' => 'required|numeric',
            // 'jumlah' => 'required|numeric',
        ];
    }
}
