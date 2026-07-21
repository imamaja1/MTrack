<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\SecurityLog;
use App\Models\BlockedIp;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    use WithPagination;

    public $ipToBlock = '';
    public $blockReason = '';

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
        $this->dispatch('modal-close', name: 'block-ip-modal');
    }

    public function unblockIp($id)
    {
        BlockedIp::find($id)?->delete();
    }
};
?>

<div>
    <div class="flex flex-col gap-6">
        <!-- Blocked IPs -->
        <flux:card>
            <div class="flex items-center justify-between mb-4">
                <flux:heading size="lg">Blocked IPs</flux:heading>
                <flux:modal.trigger name="block-ip-modal">
                    <flux:button variant="primary" icon="plus">Block New IP</flux:button>
                </flux:modal.trigger>
            </div>
            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>IP Address</flux:table.column>
                        <flux:table.column>Reason</flux:table.column>
                        <flux:table.column>Blocked By</flux:table.column>
                        <flux:table.column>Blocked At</flux:table.column>
                        <flux:table.column>Actions</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($this->blockedIps as $blocked)
                            <flux:table.row>
                                <flux:table.cell>{{ $blocked->ip_address }}</flux:table.cell>
                                <flux:table.cell>{{ $blocked->reason }}</flux:table.cell>
                                <flux:table.cell>{{ $blocked->blocker?->name }}</flux:table.cell>
                                <flux:table.cell>{{ $blocked->created_at->format('Y-m-d H:i:s') }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:button wire:click="unblockIp({{ $blocked->id }})" wire:confirm="Are you sure you want to unblock this IP?" variant="danger" size="sm">Unblock</flux:button>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
            <div class="mt-4">
                {{ $this->blockedIps->links() }}
            </div>
        </flux:card>

        <!-- Security Logs -->
        <flux:card>
            <div class="flex items-center justify-between mb-4">
                <flux:heading size="lg">Audit Logs</flux:heading>
            </div>
            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Timestamp</flux:table.column>
                        <flux:table.column>IP Address</flux:table.column>
                        <flux:table.column>Event Type</flux:table.column>
                        <flux:table.column>URL</flux:table.column>
                        <flux:table.column>User ID</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($this->securityLogs as $log)
                            <flux:table.row>
                                <flux:table.cell>{{ $log->created_at->format('Y-m-d H:i:s') }}</flux:table.cell>
                                <flux:table.cell>{{ $log->ip_address }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge color="{{ $log->event_type === 'failed_login' ? 'red' : 'orange' }}">
                                        {{ str_replace('_', ' ', Str::title($log->event_type)) }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell class="truncate max-w-xs" title="{{ $log->url_accessed }}">{{ $log->url_accessed }}</flux:table.cell>
                                <flux:table.cell>{{ $log->user_id ?? 'Guest' }}</flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
            <div class="mt-4">
                {{ $this->securityLogs->links() }}
            </div>
        </flux:card>

        <!-- Block IP Modal -->
        <flux:modal name="block-ip-modal" class="md:w-96">
            <form wire:submit.prevent="blockIp" class="flex flex-col gap-4">
                <flux:heading size="lg">Block IP Address</flux:heading>
                
                <flux:input wire:model="ipToBlock" label="IP Address" placeholder="192.168.1.1" required />
                <flux:select wire:model="blockReason" label="Reason" required>
                    <option value="" disabled>Pilih alasan...</option>
                    <option value="Suspicious activity">Suspicious activity</option>
                    <option value="Multiple failed logins">Multiple failed logins</option>
                    <option value="Spam / Bot activity">Spam / Bot activity</option>
                    <option value="Unauthorized access attempt">Unauthorized access attempt</option>
                    <option value="Other">Other</option>
                </flux:select>
                
                <div class="flex justify-end gap-2 mt-4">
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">Block IP</flux:button>
                </div>
            </form>
        </flux:modal>
    </div>
</div>