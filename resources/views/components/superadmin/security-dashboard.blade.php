<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\SecurityLog;
use App\Models\BlockedIp;
use Illuminate\Support\Facades\Auth;
use Mary\Traits\Toast;

new class extends Component
{
    use WithPagination, Toast;

    public $ipToBlock = '';
    public $blockReason = '';
    public bool $showBlockIpModal = false;

    public function getSecurityLogsProperty()
    {
        return SecurityLog::with('user')->latest()->paginate(10, ['*'], 'logsPage');
    }

    public function getBlockedIpsProperty()
    {
        return BlockedIp::with('blocker')->latest()->paginate(10, ['*'], 'blockedPage');
    }

    public function blockIp()
    {
        $this->validate([
            'ipToBlock' => 'required|ip|unique:blocked_ips,ip_address',
            'blockReason' => 'required|string|max:255',
        ]);

        BlockedIp::create([
            'ip_address' => $this->ipToBlock,
            'reason' => $this->blockReason,
            'blocked_by' => Auth::id(),
        ]);

        $this->reset(['ipToBlock', 'blockReason']);
        $this->showBlockIpModal = false;
        $this->success('IP address blocked successfully.');
    }

    public function unblockIp($id)
    {
        BlockedIp::find($id)?->delete();
        $this->success('IP address unblocked.');
    }
};
?>

<div>
    <div class="flex flex-col gap-6">
        <!-- Blocked IPs -->
        <x-card class="bg-base-100 shadow-sm border border-base-200">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-base-content">Blocked IPs</h2>
                <x-button label="Block New IP" icon="o-plus" wire:click="$set('showBlockIpModal', true)" class="btn-primary" />
            </div>
            <div class="overflow-x-auto">
                <table class="table w-full">
                    <thead>
                        <tr>
                            <th>IP Address</th>
                            <th>Reason</th>
                            <th>Blocked By</th>
                            <th>Blocked At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->blockedIps as $blocked)
                            <tr>
                                <td class="font-mono text-sm font-semibold">{{ $blocked->ip_address }}</td>
                                <td>{{ $blocked->reason }}</td>
                                <td>{{ $blocked->blocker?->name }}</td>
                                <td class="text-xs">{{ $blocked->created_at->format('Y-m-d H:i:s') }}</td>
                                <td>
                                    <x-button label="Unblock" wire:click="unblockIp({{ $blocked->id }})" wire:confirm="Are you sure you want to unblock this IP?" class="btn-error btn-sm text-white" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-base-content/60 py-4">No blocked IPs found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                {{ $this->blockedIps->links() }}
            </div>
        </x-card>

        <!-- Security Logs -->
        <x-card class="bg-base-100 shadow-sm border border-base-200">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-base-content">Audit Logs</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="table w-full">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>IP Address</th>
                            <th>Event Type</th>
                            <th>URL</th>
                            <th>User ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->securityLogs as $log)
                            <tr>
                                <td class="text-xs">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                <td class="font-mono text-sm">{{ $log->ip_address }}</td>
                                <td>
                                    <x-badge value="{{ str_replace('_', ' ', Str::title($log->event_type)) }}" class="{{ $log->event_type === 'failed_login' ? 'badge-error' : 'badge-warning' }} badge-sm" />
                                </td>
                                <td class="truncate max-w-xs" title="{{ $log->url_accessed }}">{{ $log->url_accessed }}</td>
                                <td>{{ $log->user_id ?? 'Guest' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-base-content/60 py-4">No audit logs found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                {{ $this->securityLogs->links() }}
            </div>
        </x-card>

        <!-- Block IP Modal -->
        <x-modal wire:model="showBlockIpModal" title="Block IP Address">
            <x-form wire:submit="blockIp">
                <x-input wire:model="ipToBlock" label="IP Address" placeholder="192.168.1.1" required />
                <x-select
                    wire:model="blockReason"
                    label="Reason"
                    :options="[
                        ['id' => 'Suspicious activity', 'name' => 'Suspicious activity'],
                        ['id' => 'Multiple failed logins', 'name' => 'Multiple failed logins'],
                        ['id' => 'Spam / Bot activity', 'name' => 'Spam / Bot activity'],
                        ['id' => 'Unauthorized access attempt', 'name' => 'Unauthorized access attempt'],
                        ['id' => 'Other', 'name' => 'Other']
                    ]"
                    placeholder="Pilih alasan..."
                    placeholder-value=""
                    required
                />
                
                <x-slot:actions>
                    <x-button label="Cancel" wire:click="$set('showBlockIpModal', false)" class="btn-ghost" />
                    <x-button label="Block IP" type="submit" class="btn-primary" spinner="blockIp" />
                </x-slot:actions>
            </x-form>
        </x-modal>
    </div>
</div>