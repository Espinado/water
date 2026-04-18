<?php

namespace App\Services;

use App\Contracts\VisionDocumentTextDetector;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Cloud\Vision\V1\AnnotateImageRequest;
use Google\Cloud\Vision\V1\BatchAnnotateImagesRequest;
use Google\Cloud\Vision\V1\Client\ImageAnnotatorClient;
use Google\Cloud\Vision\V1\Feature;
use Google\Cloud\Vision\V1\Feature\Type as FeatureType;
use Google\Cloud\Vision\V1\Image;
use Google\Cloud\Vision\V1\TextAnnotation;

class GoogleCloudVisionDocumentTextDetector implements VisionDocumentTextDetector
{
    /**
     * @param  array<string, mixed>  $serviceAccountJson
     * @return array{annotation: ?TextAnnotation, error: ?string}
     */
    public function detect(string $imageBinary, array $serviceAccountJson): array
    {
        $client = null;

        try {
            $scopes = ImageAnnotatorClient::$serviceScopes;
            $credentials = new ServiceAccountCredentials($scopes, $serviceAccountJson);

            $client = new ImageAnnotatorClient([
                'credentials' => $credentials,
                'transport' => config('google_vision.transport', 'rest'),
            ]);

            $feature = (new Feature)->setType(FeatureType::DOCUMENT_TEXT_DETECTION);

            $request = (new AnnotateImageRequest)
                ->setImage((new Image)->setContent($imageBinary))
                ->setFeatures([$feature]);

            $batch = BatchAnnotateImagesRequest::build([$request]);
            $response = $client->batchAnnotateImages($batch);

            $first = $response->getResponses()->offsetGet(0);
            if ($first === null) {
                return ['annotation' => null, 'error' => 'Пустой ответ Vision API.'];
            }

            if ($first->hasError()) {
                return ['annotation' => null, 'error' => 'Vision API: '.$first->getError()->getMessage()];
            }

            $annotation = $first->getFullTextAnnotation();

            return ['annotation' => $annotation, 'error' => null];
        } finally {
            if ($client instanceof ImageAnnotatorClient) {
                $client->close();
            }
        }
    }
}
