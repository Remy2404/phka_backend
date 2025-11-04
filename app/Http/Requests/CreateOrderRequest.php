<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'billing_address_id' => 'required|exists:addresses,id',
            'shipping_address_id' => 'required|exists:addresses,id',
            'payment_method' => 'required|in:credit_card,debit_card,paypal,bank_transfer,cash_on_delivery',
            'notes' => 'nullable|string|max:1000',
            'gift_card_code' => 'nullable|string|exists:gift_cards,code',
            'use_loyalty_points' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'billing_address_id.required' => 'Billing address is required.',
            'billing_address_id.exists' => 'Selected billing address does not exist.',
            'shipping_address_id.required' => 'Shipping address is required.',
            'shipping_address_id.exists' => 'Selected shipping address does not exist.',
            'payment_method.required' => 'Payment method is required.',
            'payment_method.in' => 'Please select a valid payment method.',
            'notes.max' => 'Notes cannot exceed 1000 characters.',
            'gift_card_code.exists' => 'Invalid gift card code.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'billing_address_id' => 'billing address',
            'shipping_address_id' => 'shipping address',
            'payment_method' => 'payment method',
            'gift_card_code' => 'gift card code',
            'use_loyalty_points' => 'loyalty points usage',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $billingAddressId = $this->input('billing_address_id');
            $shippingAddressId = $this->input('shipping_address_id');
            $user = $this->user();

            // Ensure addresses belong to the authenticated user
            if ($billingAddressId) {
                $billingAddress = \App\Models\Address::where('id', $billingAddressId)
                    ->where('user_id', $user->id)
                    ->first();

                if (!$billingAddress) {
                    $validator->errors()->add('billing_address_id', 'Billing address does not belong to you.');
                }
            }

            if ($shippingAddressId) {
                $shippingAddress = \App\Models\Address::where('id', $shippingAddressId)
                    ->where('user_id', $user->id)
                    ->first();

                if (!$shippingAddress) {
                    $validator->errors()->add('shipping_address_id', 'Shipping address does not belong to you.');
                }
            }

            // Check if user has items in cart
            $cart = \App\Models\ShoppingCart::where('user_id', $user->id)->first();
            if (!$cart || $cart->items->isEmpty()) {
                $validator->errors()->add('cart', 'Your cart is empty. Add items to your cart before placing an order.');
            }

            // Validate gift card if provided
            $giftCardCode = $this->input('gift_card_code');
            if ($giftCardCode) {
                $giftCard = \App\Models\GiftCard::where('code', $giftCardCode)->first();
                if ($giftCard && (!$giftCard->is_active || $giftCard->expires_at < now())) {
                    $validator->errors()->add('gift_card_code', 'Gift card is expired or inactive.');
                }
            }
        });
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert boolean strings to actual booleans
        if ($this->has('use_loyalty_points')) {
            $this->merge(['use_loyalty_points' => filter_var($this->input('use_loyalty_points'), FILTER_VALIDATE_BOOLEAN)]);
        }
    }
}