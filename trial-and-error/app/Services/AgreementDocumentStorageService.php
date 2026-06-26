<?php

namespace App\Services;

use App\Models\Agreement;
use App\Models\AgreementVersion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AgreementDocumentStorageService
{
    public function storeCurrentDocument(Agreement $agreement, UploadedFile $file): string
    {
        return $file->storeAs(
            $this->currentDirectory($agreement),
            $this->sanitizeFileName($file->getClientOriginalName()),
            'public'
        );
    }

    public function storeVersionSnapshot(Agreement $agreement, ?string $documentPath, ?int $uploadedById = null, ?string $uploadedByName = null): ?AgreementVersion
    {
        if (! $documentPath) {
            return null;
        }

        $nextVersion = $agreement->versions()->count() + 1;
        $versionPath = $this->versionPath($agreement, $nextVersion, $documentPath);

        if (Storage::disk('public')->exists($documentPath)) {
            Storage::disk('public')->copy($documentPath, $versionPath);
        } else {
            $versionPath = $documentPath;
        }

        return AgreementVersion::create([
            'agreement_id' => $agreement->id,
            'version' => 'v'.$nextVersion,
            'document' => $versionPath,
            'uploaded_by' => $uploadedByName ?? auth()->user()->name,
            'uploaded_by_id' => $uploadedById ?? auth()->id(),
        ]);
    }

    public function currentDirectory(Agreement $agreement): string
    {
        return "agreements/{$agreement->id}/current";
    }

    public function versionDirectory(Agreement $agreement, int $versionNumber): string
    {
        return "agreements/{$agreement->id}/versions/v{$versionNumber}";
    }

    public function versionPath(Agreement $agreement, int $versionNumber, string $documentPath): string
    {
        return $this->versionDirectory($agreement, $versionNumber).'/'.basename($documentPath);
    }

    private function sanitizeFileName(string $fileName): string
    {
        $fileName = basename($fileName);
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $name = pathinfo($fileName, PATHINFO_FILENAME);

        $safeName = preg_replace('#[\\\\/]+#', '_', $name);
        $safeName = preg_replace('/[<>:"|?*\x00-\x1F]+/', '_', $safeName);
        $safeName = trim($safeName, ' .-_');

        if ($safeName === '') {
            $safeName = 'document';
        }

        return $safeName.($extension ? ".{$extension}" : '');
    }
}
