{{-- File: resources/views/filament/tables/columns/quota-progress.blade.php --}}

<div class="w-full max-w-xs">
    @php
        $percentage = $getRecord()->usage_percentage;
        $used = $getRecord()->used_quota;
        $total = $getRecord()->total_quota;
        $available = $getRecord()->available_quota;
        
        // Determine color based on usage
        if ($percentage >= 100) {
            $colorClass = 'bg-red-500';
            $bgColorClass = 'bg-red-100';
        } elseif ($percentage >= 90) {
            $colorClass = 'bg-yellow-500';
            $bgColorClass = 'bg-yellow-100';
        } elseif ($percentage >= 70) {
            $colorClass = 'bg-blue-500';
            $bgColorClass = 'bg-blue-100';
        } else {
            $colorClass = 'bg-green-500';
            $bgColorClass = 'bg-green-100';
        }
    @endphp
    
    <div class="space-y-1">
        {{-- Progress Bar --}}
        <div class="w-full {{ $bgColorClass }} rounded-full h-2.5">
            <div class="{{ $colorClass }} h-2.5 rounded-full transition-all duration-300" 
                 style="width: {{ min(100, $percentage) }}%"></div>
        </div>
        
        {{-- Numbers --}}
        <div class="flex justify-between items-center text-xs text-gray-600">
            <span class="font-medium">{{ $used }}/{{ $total }}</span>
            <span class="text-gray-500">{{ $percentage }}%</span>
        </div>
        
        {{-- Available Count --}}
        @if($available > 0)
            <div class="text-xs text-green-600 font-medium">
                +{{ $available }} tersisa
            </div>
        @else
            <div class="text-xs text-red-600 font-medium">
                Penuh
            </div>
        @endif
    </div>
</div>