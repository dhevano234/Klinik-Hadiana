<?php
// File: app/Filament/Dokter/Resources/QueueResource/Pages/ListQueues.php
// UPDATED: Tambah Global Pending untuk Dokter

namespace App\Filament\Dokter\Resources\QueueResource\Pages;

use App\Filament\Dokter\Resources\QueueResource;
use App\Models\Queue;
use App\Services\QueueService;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

class ListQueues extends ListRecords
{
    protected static string $resource = QueueResource::class;
    protected static ?string $title = 'Kelola Antrian';

    protected QueueService $queueService;

    public function boot()
    {
        $this->queueService = app(QueueService::class);
    }

    protected function getHeaderActions(): array
    {
        return [
            //  TOMBOL PENDING SEMUA ANTRIAN (dengan auto-activate global pending)
            Actions\Action::make('pendingAllWaiting')
                ->label('Pending Semua Antrian')
                ->icon('heroicon-o-pause')
                ->color('warning')
                ->size('sm')
                ->requiresConfirmation()
                ->modalHeading('Pending Semua Antrian Menunggu')
                ->modalDescription('Semua antrian dengan status "Menunggu" akan dijeda. Antrian baru yang diambil juga akan otomatis pending sampai di-resume.')
                ->action(function () {
                    $result = $this->queueService->pendingAllWaitingQueues();
                    
                    if ($result['success']) {
                        Notification::make()
                            ->title('Berhasil Pending Semua Antrian!')
                            ->body($result['message'] . ' Antrian baru akan otomatis pending.')
                            ->warning()
                            ->duration(8000)
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Tidak Ada Antrian')
                            ->body($result['message'])
                            ->warning()
                            ->send();
                    }
                }),

            //  TOMBOL RESUME SEMUA ANTRIAN (dengan auto-deactivate global pending)
            Actions\Action::make('resumeAllPending')
                ->label('Resume Semua Antrian')
                ->icon('heroicon-o-play')
                ->color('success')
                ->size('sm')
                ->requiresConfirmation()
                ->modalHeading('Resume Semua Antrian Pending')
                ->modalDescription('Semua antrian yang dijeda akan dilanjutkan. Antrian baru akan kembali berjalan normal.')
                ->action(function () {
                    $result = $this->queueService->resumeAllPendingQueues();
                    
                    if ($result['success']) {
                        Notification::make()
                            ->title('Berhasil Resume Semua Antrian!')
                            ->body($result['message'] . ' Antrian baru akan berjalan normal.')
                            ->success()
                            ->duration(8000)
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Tidak Ada Antrian Pending')
                            ->body($result['message'])
                            ->warning()
                            ->send();
                    }
                }),
        ];
    }
}