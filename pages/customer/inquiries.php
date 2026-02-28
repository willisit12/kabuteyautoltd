<?php
/**
 * pages/customer/inquiries.php
 * Concierge Correspondence center - Threaded Chat Edition
 */
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/layout/customer-layout.php';

requireAuth();
$user = getUserInfo();
$db = getDB();
$active_id = intval($_GET['id'] ?? 0);

// Handle Message Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message']) && $active_id) {
    // Verify ownership
    $stmt = $db->prepare("SELECT id FROM inquiries WHERE id = ? AND user_id = ?");
    $stmt->execute([$active_id, $user['id']]);
    if ($stmt->fetch()) {
        sendInquiryMessage($active_id, $user['id'], clean($_POST['message']));
        setFlash('success', 'Message dispatched to our concierge team.');
    }
    redirect(url('customer/inquiries.php?id=' . $active_id));
}

// Fetch all inquiries for the sidebar/list
$stmt = $db->prepare("
    SELECT i.*, c.make, c.model, c.year, c.slug,
           (SELECT url FROM car_images WHERE car_id = c.id LIMIT 1) as primary_image
    FROM inquiries i
    LEFT JOIN cars c ON i.car_id = c.id
    WHERE i.user_id = ?
    ORDER BY i.created_at DESC
");
$stmt->execute([$user['id']]);
$inquiries = $stmt->fetchAll();

// Fetch active thread if selected
$active_inquiry = null;
$messages = [];
if ($active_id) {
    foreach ($inquiries as $inq) {
        if ($inq['id'] === $active_id) {
            $active_inquiry = $inq;
            $messages = getInquiryMessages($active_id);
            break;
        }
    }
}

$success = getFlash('success');

ob_start();
?>

<div class="mb-12">
    <h1 class="text-4xl font-black text-foreground tracking-tighter uppercase leading-none mb-2">Concierge <span class="text-gradient">Correspondence.</span></h1>
    <p class="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground opacity-60">Private communication channel with our executive team</p>
</div>

<?php if (empty($inquiries)): ?>
    <div class="glass p-20 rounded-[4rem] border border-dashed border-border/50 text-center">
        <i class="fas fa-comment-dots text-5xl text-muted-foreground/20 mb-6"></i>
        <p class="text-sm font-bold text-muted-foreground italic uppercase tracking-widest">No Active Correspondence</p>
        <a href="<?php echo url('cars'); ?>" class="text-accent font-black uppercase tracking-widest text-[10px] hover:underline mt-4 inline-block">Initiate Secure Channel from Showroom</a>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 h-[800px]">
        <!-- Thread List -->
        <div class="lg:col-span-4 glass rounded-[3rem] border border-border/50 overflow-hidden flex flex-col shadow-2xl">
            <div class="p-8 border-b border-border/30 bg-muted/30">
                <h3 class="text-[10px] font-black uppercase tracking-widest text-foreground">Communication Threads</h3>
            </div>
            <div class="flex-1 overflow-y-auto divide-y divide-border/20">
                <?php foreach ($inquiries as $inq): ?>
                    <a href="?id=<?php echo $inq['id']; ?>" class="block p-8 hover:bg-accent/[0.03] transition-all relative group <?php echo $active_id === $inq['id'] ? 'bg-accent/5' : ''; ?>">
                        <?php if ($inq['status'] === 'REPLIED'): ?>
                            <div class="absolute top-8 right-8 w-2 h-2 bg-green-500 rounded-full animate-pulse shadow-[0_0_10px_rgba(34,197,94,0.5)]"></div>
                        <?php endif; ?>
                        
                        <div class="flex items-center gap-4 mb-3">
                            <span class="text-[8px] font-black uppercase tracking-widest text-accent"><?php echo $inq['status']; ?></span>
                            <span class="text-[8px] font-bold text-muted-foreground opacity-60"><?php echo date('M d', strtotime($inq['created_at'])); ?></span>
                        </div>
                        <h4 class="text-xs font-black text-foreground uppercase tracking-tight mb-2 truncate group-hover:text-accent transition-colors"><?php echo clean($inq['subject'] ?: 'Vehicle Inquiry'); ?></h4>
                        <p class="text-[10px] font-medium text-muted-foreground italic truncate">"<?php echo clean($inq['message']); ?>"</p>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Active Chat -->
        <div class="lg:col-span-8 glass rounded-[4rem] border border-border/50 shadow-2xl flex flex-col overflow-hidden relative">
            <?php if ($active_inquiry): ?>
                <!-- Chat Header -->
                <div class="p-8 border-b border-border/30 bg-muted/40 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-black text-foreground uppercase tracking-tighter leading-none mb-1"><?php echo clean($active_inquiry['subject']); ?></h2>
                        <?php if ($active_inquiry['car_id']): ?>
                            <a href="<?php echo url('car-detail/' . $active_inquiry['slug']); ?>" class="text-[8px] font-black text-accent uppercase tracking-widest hover:underline"><?php echo $active_inquiry['year'] . ' ' . $active_inquiry['make'] . ' ' . $active_inquiry['model']; ?></a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Messages Window -->
                <div class="flex-1 overflow-y-auto p-10 space-y-10" id="chat-window">
                    <?php foreach ($messages as $msg): ?>
                        <?php $isMe = ($msg['sender_id'] == $user['id']); ?>
                        <div class="flex <?php echo $isMe ? 'justify-end' : 'justify-start'; ?>">
                            <div class="max-w-[80%] <?php echo $isMe ? 'items-end' : 'items-start'; ?> flex flex-col gap-3">
                                <div class="px-8 py-5 rounded-[2.5rem] text-sm font-medium leading-relaxed <?php echo $isMe ? 'bg-accent text-white shadow-[0_10px_30px_rgba(249,115,22,0.2)] rounded-tr-none' : 'glass border border-border/50 text-foreground rounded-tl-none'; ?>">
                                    <?php echo nl2br(clean($msg['message'])); ?>
                                </div>
                                <span class="text-[8px] font-black uppercase tracking-widest text-muted-foreground opacity-50 px-2">
                                    <?php echo $isMe ? 'Sent by You' : 'Expert Dispatch'; ?> 
                                    • <?php echo date('M d, H:i', strtotime($msg['created_at'])); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Input Area -->
                <div class="p-10 border-t border-border/30">
                    <form method="POST" class="relative group">
                        <textarea 
                            name="message" 
                            placeholder="Compose your reply to the executive team..."
                            required
                            class="w-full bg-background/50 border border-border/50 rounded-3xl p-6 pr-40 text-sm font-medium focus:ring-2 focus:ring-accent outline-none min-h-[140px] transition-all"
                        ></textarea>
                        <div class="absolute bottom-6 right-6">
                            <button type="submit" class="bg-foreground text-background px-10 py-4 rounded-2xl font-black uppercase tracking-widest text-[9px] shadow-xl hover:bg-accent hover:text-white transition-all flex items-center gap-3 active:scale-95 group-hover:shadow-[0_15px_40px_rgba(249,115,22,0.3)]">
                                Dispatch <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="flex-1 flex flex-col items-center justify-center p-20 text-center">
                    <div class="w-24 h-24 bg-accent/10 border border-accent/20 rounded-[2rem] flex items-center justify-center text-accent mb-8">
                        <i class="fas fa-comments text-3xl"></i>
                    </div>
                    <h3 class="text-2xl font-black text-foreground uppercase tracking-tighter mb-4">Secure Channel Ready</h3>
                    <p class="text-sm font-medium text-muted-foreground italic max-w-sm mb-10">Select a communication thread from the list to synchronize your secure correspondence with our elite concierge team.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<script>
    const chatWindow = document.getElementById('chat-window');
    if (chatWindow) chatWindow.scrollTop = chatWindow.scrollHeight;
</script>

<?php
$content = ob_get_clean();
renderCustomerLayout($content, 'Correspondence Center');
?>
