<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;

class DatatableService
{
    public function extractParameters(Request $request): array
    {
        $searchData = $request->query->all('search');

        return [
            'draw' => $request->query->getInt('draw'),
            'start' => $request->query->getInt('start'),
            'length' => $request->query->getInt('length'),
            'search' => $searchData['value'] ?? '',
            'order' => $request->query->all('order') ?? []
        ];
    }
}
