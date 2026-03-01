<?php
/**
 * pages/customer/notifications.php
 * Command Center - All Notifications
 */
require_once __DIR__ . '/../../includes/layout/customer-layout.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAuth();

$db = getDB();
$user = getUserInfo();

// Fetch all notifications
$stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$notifications = $stmt->fetchAll();

ob_start();
?>

<div class="mb-12 flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-black text-foreground tracking-tighter uppercase mb-2">Command Center</h1>
        <p class="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground opacity-60">Intelligence Alerts & Updates</p>
    </div>
</div>

<div class="glass p-8 md:p-12 rounded-[3rem] border border-border shadow-2xl"
     x-data="{
        notifications: <?php echo htmlspecialchars(json_encode($notifications), ENT_QUOTES, 'UTF-8'); ?>,
        
        async markAsRead(id, link = null) {
            let notif = this.notifications.find(n => n.id == id);
            if (!notif) return;
            notif.is_read = 1;
            try {
                await fetch('<?php echo url('api/notifications-api.php'); ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'mark_read', id: id })
                });
            } catch (e) { console.error(e); }
            if (link) {
                window.location.href = link;
            }
        },

        async deleteNotification(id) {
            Swal.fire({
                title: 'CLEAR INTELLIGENCE?',
                text: 'This alert will be permanently erased.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: 'rgb(var(--muted-rgb))',
                background: 'rgb(var(--background-rgb))',
                color: 'rgb(var(--foreground-rgb))',
                confirmButtonText: '<i class=\'fas fa-eraser mr-2\'></i> ERASE',
                cancelButtonText: 'CANCEL'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    this.notifications = this.notifications.filter(n => n.id != id);
                    try {
                        await fetch('<?php echo url('api/notifications-api.php'); ?>', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ action: 'delete', id: id })
                        });
                    } catch(e) { console.error(e); }
                }
            });
        }
     }">
     
    <template x-if="notifications.length === 0">
        <div class="py-20 text-center text-muted-foreground">
            <div class="w-24 h-24 rounded-full bg-muted/50 flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-bell-slash text-4xl opacity-50"></i>
            </div>
            <h2 class="text-xl font-black uppercase tracking-tighter text-foreground mb-2">No Active Intel</h2>
            <p class="text-xs font-bold uppercase tracking-widest opacity-60">Your command center is clear.</p>
        </div>
    </template>

    <div class="space-y-4">
        <template x-for="notif in notifications" :key="notif.id">
            <div class="p-6 md:p-8 rounded-3xl border transition-all flex flex-col md:flex-row gap-6 md:items-center justify-between group"
                 :class="notif.is_read == 0 ? 'bg-accent/5 border-accent/20' : 'bg-background border-border hover:bg-muted/30'">
                
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0"
                         :class="{
                             'bg-blue-500/10 text-blue-500': notif.type === 'INFO',
                             'bg-green-500/10 text-green-500': notif.type === 'SUCCESS',
                             'bg-yellow-500/10 text-yellow-500': notif.type === 'WARNING',
                             'bg-red-500/10 text-red-500': notif.type === 'ERROR'
                         }">
                         <i class="fas text-xl"
                            :class="{
                                'fa-info-circle': notif.type === 'INFO',
                                'fa-check-circle': notif.type === 'SUCCESS',
                                'fa-exclamation-triangle': notif.type === 'WARNING',
                                'fa-times-circle': notif.type === 'ERROR'
                            }"></i>
                    </div>

                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <h3 class="text-sm font-black uppercase tracking-widest text-foreground" x-text="notif.title"></h3>
                            <template x-if="notif.is_read == 0">
                                <span class="px-2 py-0.5 bg-accent/10 border border-accent text-accent rounded-full text-[8px] font-black uppercase tracking-widest">Unread</span>
                            </template>
                        </div>
                        <p class="text-sm text-muted-foreground font-medium mb-3" x-text="notif.message"></p>
                        <p class="text-[9px] font-black uppercase tracking-widest text-muted-foreground/60" x-text="new Date(notif.created_at).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })"></p>
                    </div>
                </div>

                <div class="flex items-center gap-3 flex-shrink-0 justify-end">
                    <template x-if="notif.link">
                        <button @click="markAsRead(notif.id, '<?php echo rtrim(SITE_URL, '/'); ?>/' + notif.link.replace(/^\//, ''))"
                           class="w-10 h-10 rounded-xl bg-muted/50 text-foreground flex items-center justify-center hover:bg-accent hover:text-white transition-all outline-none" title="View Target">
                            <i class="fas fa-external-link-alt text-sm"></i>
                        </button>
                    </template>
                    <button @click="deleteNotification(notif.id)"
                            class="w-10 h-10 rounded-xl bg-red-500/10 text-red-500 flex items-center justify-center hover:bg-red-500 hover:text-white transition-all outline-none opacity-0 group-hover:opacity-100 focus:opacity-100" title="Delete Alert">
                        <i class="fas fa-trash-alt text-sm"></i>
                    </button>
                    <template x-if="notif.is_read == 0 && !notif.link">
                        <button @click="markAsRead(notif.id)"
                                class="px-4 h-10 rounded-xl text-[9px] font-black uppercase tracking-widest bg-accent text-white hover:bg-accent/80 transition-all outline-none">
                            Mark Read
                        </button>
                    </template>
                </div>
            </div>
        </template>
    </div>
</div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
$content = ob_get_clean();
renderCustomerLayout($content, 'Command Center');
?>
