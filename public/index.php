<?php
require __DIR__ . '/../vendor/autoload.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use setasign\Fpdi\Tcpdf\Fpdi;
use Smalot\PdfParser\Parser;

$app = AppFactory::create();

$app->post('/extract', function (Request $request, Response $response, $args) {
    $uploadedFiles = $request->getUploadedFiles();

    if (empty($uploadedFiles['file'])) {
        $response->getBody()->write(json_encode(['error' => 'No file uploaded']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    $file = $uploadedFiles['file'];
    if ($file->getError() !== UPLOAD_ERR_OK) {
        $response->getBody()->write(json_encode(['error' => 'Failed to upload file']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    $pdfPath = __DIR__ . '/' . $file->getClientFilename();
    $file->moveTo($pdfPath);

    $data = extractPdfData($pdfPath);
    $response->getBody()->write(json_encode($data));

    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});

function extractPdfData($pdfPath) {
    $parser = new Parser();
    $pdf = $parser->parseFile($pdfPath);
    $details = $pdf->getDetails();

    $data = [
        'metadata' => $details,
        'text' => []
    ];

    // Extract text from each page
    foreach ($pdf->getPages() as $pageNo => $page) {
        $data['text'][] = [
            'page' => $pageNo + 1,
            'text' => $page->getText()
        ];
    }

    // Note: FPDI/TCPDF does not support direct image extraction; you'd need additional code for that.

    return $data;
}

$app->run();
