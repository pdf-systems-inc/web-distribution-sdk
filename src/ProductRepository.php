<?php

namespace Pdfsystems\WebDistributionSdk;

use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use Pdfsystems\WebDistributionSdk\Dtos\Company;
use Pdfsystems\WebDistributionSdk\Dtos\FreightResponse;
use Pdfsystems\WebDistributionSdk\Dtos\Product;
use Pdfsystems\WebDistributionSdk\Exceptions\NotFoundException;
use Pdfsystems\WebDistributionSdk\Exceptions\ResponseException;
use Pdfsystems\WebDistributionSdk\Requests\FreightRequest;
use Spatie\DataTransferObject\Exceptions\UnknownProperties;

class ProductRepository extends AbstractRepository
{
    /**
     * @param Company $company
     * @param callable $callback
     * @param array $options
     * @param int $perPage
     * @return void
     * @throws GuzzleException
     * @throws UnknownProperties
     */
    public function iterate(Company $company, callable $callback, array $options = [], int $perPage = 128): void
    {
        /**
         * TODO: Since the current WD API does not allow both company and line to be specified, if the caller of this
         * function passes in a line_id as part of the $options parameter that is not for the company specified in the
         * $company parameter, they will receive products that do not match the specified company.
         * This scenario should probably be addressed by performing a check before loading any products that the company
         * for the specified line matches the company passed in.
         */

        $requestOptions = [
            'with' => [
                'style.productCategoryCode',
                'style.primaryPrice',
                'company',
                'line',
                'primaryBook',
                'style.sellingUnit',
                'style.millUnit',
            ],
            'count' => $perPage,
            'page' => 1,
        ];

        if (! empty($options['line_id'])) {
            $requestOptions['line'] = $options['line_id'];
        } else {
            $requestOptions['company'] = $company->id;
        }

        do {
            $response = $this->client->getJson('api/item', $requestOptions);

            foreach ($response as $product) {
                $callback(new Product($product));
            }

            $requestOptions['page']++;
        } while (! empty($response));
    }

    /**
     * @throws UnknownProperties
     * @throws GuzzleException
     * @throws ResponseException
     */
    public function find(Company $company, string $itemNumber): Product
    {
        $requestOptions = [
            'company' => $company->id,
            'search' => '#' . $itemNumber,
            'trashed' => 'true',
            'with' => [
                'style.productCategoryCode',
                'style.primaryPrice',
                'company',
                'discontinueCode',
                'line',
                'primaryBook',
                'style.sellingUnit',
                'style.millUnit',
            ],
        ];
        $response = $this->client->getJson('api/item', $requestOptions);
        if (count($response) > 0) {
            return new Product($response[0]);
        } else {
            throw new NotFoundException();
        }
    }

    /**
     * @throws UnknownProperties
     * @throws GuzzleException
     * @throws ResponseException
     */
    public function findById(int $id): Product
    {
        $requestOptions = [
            'with' => [
                'style.productCategoryCode',
                'style.primaryPrice',
                'company',
                'discontinueCode',
                'line',
                'primaryBook',
                'style.sellingUnit',
                'style.millUnit',
            ],
        ];
        try {
            return new Product(
                $this->client->getJson("api/item/$id", $requestOptions)
            );
        } catch (BadResponseException) {
            throw new NotFoundException();
        }
    }

    /**
     * @throws GuzzleException
     * @throws UnknownProperties
     */
    public function update(Product $product): Product
    {
        $this->client->putJson('api/style/' . $product->style_id, [
            'name' => $product->style_name,
            'content' => $product->content,
            'width' => $product->width,
            'repeat' => $product->repeat,
        ]);
        $this->client->putJson('api/item/' . $product->id, [
            'item_number' => $product->item_number,
            'color_name' => $product->color_name,
            'warehouse_location' => $product->warehouse_location,
            'sample_warehouse_location' => $product->warehouse_location_sample,
        ]);

        return $this->find($product->company, $product->item_number);
    }

    /**
     * @throws GuzzleException
     * @throws UnknownProperties
     */
    public function freight(Product $product, FreightRequest $request): FreightResponse
    {
        $request->validate();

        $query = [
            'postal_code' => $request->postalCode,
            'quantity' => $request->quantity,
            'country' => $request->country,
        ];

        return new FreightResponse(
            $this->client->getJson('api/item/' . $product->id . '/freight', $query)
        );
    }
}
