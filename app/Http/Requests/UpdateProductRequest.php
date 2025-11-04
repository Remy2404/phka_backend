<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Add authorization logic if needed
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $productId = $this->route('product')?->id ?? $this->route('id');

        return [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'short_description' => 'nullable|string|max:500',
            'sku' => 'sometimes|required|string|unique:products,sku,' . $productId . '|max:100',
            'price' => 'sometimes|required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'sometimes|required|integer|min:0',
            'min_stock_level' => 'nullable|integer|min:0',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|json',
            'category_id' => 'sometimes|required|exists:categories,id',
            'brand' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'images' => 'nullable|array|max:10',
            'images.*' => 'url',
            'skin_type' => 'nullable|in:oily,dry,combination,normal,sensitive',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'is_virtual' => 'boolean',
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
            'name.required' => 'Product name is required.',
            'description.required' => 'Product description is required.',
            'sku.required' => 'SKU is required.',
            'sku.unique' => 'This SKU is already in use.',
            'price.required' => 'Price is required.',
            'price.numeric' => 'Price must be a valid number.',
            'price.min' => 'Price cannot be negative.',
            'stock_quantity.required' => 'Stock quantity is required.',
            'stock_quantity.integer' => 'Stock quantity must be a whole number.',
            'stock_quantity.min' => 'Stock quantity cannot be negative.',
            'category_id.required' => 'Category is required.',
            'category_id.exists' => 'Selected category does not exist.',
            'images.max' => 'Maximum 10 images allowed.',
            'images.*.url' => 'Each image must be a valid URL.',
            'skin_type.in' => 'Please select a valid skin type.',
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
            'original_price' => 'original price',
            'cost_price' => 'cost price',
            'stock_quantity' => 'stock quantity',
            'min_stock_level' => 'minimum stock level',
            'category_id' => 'category',
            'skin_type' => 'skin type',
            'is_featured' => 'featured status',
            'is_active' => 'active status',
            'is_virtual' => 'virtual product status',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert boolean strings to actual booleans
        $booleanFields = ['is_featured', 'is_active', 'is_virtual'];

        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
                $this->merge([$field => filter_var($this->input($field), FILTER_VALIDATE_BOOLEAN)]);
            }
        }

        // Convert dimensions to JSON if it's an array
        if ($this->has('dimensions') && is_array($this->dimensions)) {
            $this->merge(['dimensions' => json_encode($this->dimensions)]);
        }

        // Validate original_price is greater than or equal to price if both are provided
        if ($this->has('original_price') && $this->has('price')) {
            $originalPrice = $this->input('original_price');
            $price = $this->input('price');

            if ($originalPrice < $price) {
                $this->getValidatorInstance()->errors()->add('original_price', 'Original price must be greater than or equal to current price.');
            }
        }
    }
}