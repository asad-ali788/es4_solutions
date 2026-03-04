<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use SellingPartnerApi\Seller\SellerConnector;
use SellingPartnerApi\Seller\FeedsV20210630\Dto\CreateFeedSpecification;
use SellingPartnerApi\Seller\FeedsV20210630\Dto\CreateFeedDocumentSpecification;
use SellingPartnerApi\FeedsApi;
use Illuminate\Support\Facades\Http;
use SellingPartnerApi\Seller\FeedsV20210630\Responses\CreateFeedDocumentResponse;

class SpFeedsController extends Controller
{
    
    public function createFeedDocument(Request $request, SellerConnector $connector): JsonResponse
    {
        try {
            $api = $connector->feedsV20210630();

            $contentType = $request->input(
                'content_type',
                'text/tab-separated-values; charset=UTF-8'
            );

            $dto = new CreateFeedDocumentSpecification($contentType);

            $response = $api->createFeedDocument($dto);
            $payload = $response->json();

            return response()->json($payload);
        } catch (\Throwable $e) {
            Log::error('SP-API createFeedDocument error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create feed document.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    

    public function uploadFeedDocuments(SellerConnector $connector): JsonResponse
    {
        try {
            $feedType       = 'JSON_LISTINGS_FEED';
            $marketplaceIds = ['ATVPDKIKX0DER'];

            // Step 1: Construct your feed content as raw JSON
            $feedData = [
                'header' => [
                    'sellerId' => 'A3TC0WDGAJEARB',
                    'version' => '2.0',
                ],
                'messages' => [
                    [
                        'messageId' => 1,
                        'sku' => 'ECOTC2RED1P',
                        'operationType' => 'PATCH',
                        'productType' => 'PRODUCT',
                        'patches' => [
                            [
                                'op' => 'replace',
                                'path' => '/attributes/purchasable_offer',
                                'value' => [
                                    [
                                        'audience' => 'ALL',
                                        'currency' => 'USD',
                                        'our_price' => [
                                            [
                                                'schedule' => [
                                                    ['value_with_tax' => 10.99]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ];

            // Step 2: Get proper content type for the feed
            $contentType = CreateFeedDocumentResponse::getContentType($feedType);

            // Step 3: Create feed document
            $feedsApi = $connector->feedsV20210630();
            $spec = new CreateFeedDocumentSpecification($contentType);
            $createDocResponse = $feedsApi->createFeedDocument($spec);
            $feedDocument = $createDocResponse->dto();

            // Step 4: Upload content using the feedDocument instance
            $feedContent = json_encode($feedData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $feedDocument->upload($feedType, $feedContent); // Automatically handles headers

            // Step 5: Submit the feed
            $createFeedSpec = new CreateFeedSpecification(
                feedType: $feedType,
                marketplaceIds: $marketplaceIds,
                inputFeedDocumentId: $feedDocument->feedDocumentId
            );
            $createFeedResponse = $feedsApi->createFeed($createFeedSpec);
            $feedId = $createFeedResponse->dto()->feedId;

            return response()->json([
                'success' => true,
                'feedId' => $feedId,
                'message' => 'Feed submitted successfully ✅'
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Feed submission failed 😞',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function checkFeedProcessingReport(SellerConnector $connector): JsonResponse
    {
        try {
            $api = $connector->feedsV20210630();

            // Step 1: Get feed status by feedId
            $feedStatusResponse = $api->getFeed(1448048020277);
            $feedStatus = $feedStatusResponse->json();

            $reportDocId = $feedStatus['resultFeedDocumentId'] ?? null;

            if (!$reportDocId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No processing report available yet for this feed.',
                    'debug' => $feedStatus,
                ]);
            }

            // Step 2: Get the report document
            $docResponse = $api->getFeedDocument($reportDocId);
            $docData = $docResponse->json();

            $downloadUrl = $docData['url'] ?? null;

            if (!$downloadUrl) {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not retrieve report download URL',
                    'debug' => $docData,
                ]);
            }

            // Step 3: Download report content
            $response = Http::get($downloadUrl);
            $body = $response->body();

            // Check if starts with GZIP magic bytes (1F 8B)
            if (substr($body, 0, 2) === "\x1f\x8b") {
                $reportContent = gzdecode($body);
            } else {
                $reportContent = $body;
            }

            return response()->json([
                'success' => true,

                'reportDocumentId' => $reportDocId,
                'report' => json_decode($reportContent),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error while retrieving feed report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    public function uploadFeedDocument(Request $request, SellerConnector $connector): JsonResponse
    {
        try {
            $api = $connector->feedsV20210630();

            $data = $request->validate([
                'content_type' => 'required|string',
                'feed_content' => 'required|string',
            ]);

            // Step 1: Create Feed Document
            $dto = new CreateFeedDocumentSpecification($data['content_type']);



            $response = $api->createFeedDocument($dto);
            $payload = $response->json();

            $feedDocumentId = $payload['feedDocumentId'];
            $uploadUrl = $payload['url'];

            // Step 2: Upload feed content to S3
            $ch = curl_init($uploadUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data['feed_content']);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: ' . $data['content_type'],
            ]);
            $s3Response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                return response()->json([
                    'success' => true,
                    'feed_document_id' => $feedDocumentId,
                    'message' => 'Feed document uploaded successfully.',
                ]);
            } else {
                
                Log::error('S3 Upload error', [
                    'status' => $httpCode,
                    'response' => $s3Response,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Upload to S3 failed.',
                    'status' => $httpCode,
                    'response' => $s3Response,
                ], 500);
            }
        } catch (\Throwable $e) {
            Log::error('SP-API uploadFeedDocument error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload feed document.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    
    public function createFeed(Request $request, SellerConnector $connector): JsonResponse
    {
        try {
            $api = $connector->feedsV20210630();

            $data = $request->validate([
                'feed_type'               => 'required|string',
                'marketplace_ids'         => 'required|array',
                'marketplace_ids.*'       => 'string',
                'input_feed_document_id'  => 'required|string',
                'feed_options'            => 'nullable|array',
            ]);

            $createFeedSpecification = new CreateFeedSpecification(
                $data['feed_type'],
                $data['marketplace_ids'],
                $data['input_feed_document_id'],
                $data['feed_options'] ?? null
            );

            $response = $api->createFeed($createFeedSpecification);

            $payload = $response->json();

            return response()->json($payload);
        } catch (\Throwable $e) {
            Log::error('SP-API createFeed error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create feed.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    
    public function getFeed(string $feedId, SellerConnector $connector): JsonResponse
    {
        try {
            $api = $connector->feedsV20210630();

            $response = $api->getFeed($feedId);
            $payload = $response->json();

            return response()->json($payload);
        } catch (\Throwable $e) {
            Log::error('SP-API getFeed error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch feed.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
