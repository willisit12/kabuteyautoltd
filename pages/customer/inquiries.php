<?php
/**
 * pages/customer/inquiries.php
 * Customer Chat / Inquiry Center
 */
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/layout/customer-layout.php';

requireAuth();
$user = getUserInfo();
$db   = getDB();
$active_id = intval($_GET['id'] ?? 0);

// Handle new message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message']) && $active_id) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Security validation failed.');
        redirect(url('customer/inquiries?id=' . $active_id));
    }
    $stmt = $db->prepare("SELECT id FROM inquiries WHERE id = ? AND user_id = ?");
    $stmt->execute([$active_id, $user['id']]);
    if ($stmt->fetch()) {
        sendInquiryMessage($active_id, $user['id'], clean($_POST['message']));
    }
    redirect(url('customer/inquiries?id=' . $active_id));
}

// Fetch all inquiries
$stmt = $db->prepare("
    SELECT i.*, c.make, c.model, c.year, c.slug,
           (SELECT url FROM car_images WHERE car_id = c.id LIMIT 1) as car_image,
           (SELECT COUNT(*) FROM inquiry_messages WHERE inquiry_id = i.id) as msg_count,
           (SELECT message FROM inquiry_messages WHERE inquiry_id = i.id ORDER BY created_at DESC LIMIT 1) as last_message,
           (SELECT created_at FROM inquiry_messages WHERE inquiry_id = i.id ORDER BY created_at DESC LIMIT 1) as last_message_at
    FROM inquiries i
    LEFT JOIN cars c ON i.car_id = c.id
    WHERE i.user_id = ?
    ORDER BY COALESCE(
        (SELECT created_at FROM inquiry_messages WHERE inquiry_id = i.id ORDER BY created_at DESC LIMIT 1),
        i.created_at
    ) DESC
");
$stmt->execute([$user['id']]);
$inquiries = $stmt->fetchAll();

// Active thread
$active_inquiry = null;
$messages       = [];
if ($active_id) {
    foreach ($inquiries as $inq) {
        if ((int)$inq['id'] === $active_id) {
            $active_inquiry = $inq;
            $messages       = getInquiryMessages($active_id);
            break;
        }
    }
}

ob_start();
?>

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-black text-foreground tracking-tighter uppercase leading-none mb-1">My <span class="text-gradient">Inquiries.</span></h1>
        <p class="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground opacity-60">Chat with our team about any vehicle</p>
    </div>
</div>

<?php if (empty($inquiries)): ?>
    <!-- Empty State -->
    <div class="glass p-16 rounded-[3rem] border border-dashed border-border/50 text-center">
        <div class="w-20 h-20 bg-accent/10 border border-accent/20 rounded-[1.5rem] flex items-center justify-center text-accent mx-auto mb-6">
            <i class="fas fa-comments text-3xl"></i>
        </div>
        <h3 class="text-xl font-black text-foreground uppercase tracking-tighter mb-3">No Conversations Yet</h3>
        <p class="text-sm font-medium text-muted-foreground italic max-w-sm mx-auto mb-8">
            Browse our inventory and click "Inquire" on any vehicle to start a conversation with our team.
        </p>
        <a href="<?php echo url('cars'); ?>"
           class="inline-flex items-center gap-3 px-8 py-4 bg-accent text-white rounded-2xl font-black uppercase tracking-widest text-[10px] shadow-lg hover:scale-[1.03] transition-all">
            <i class="fas fa-car"></i> Browse Inventory
        </a>
    </div>

<?php else: ?>
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6" style="height: calc(100vh - 220px); min-height: 600px;">

        <!-- Thread List -->
        <div class="lg:col-span-4 glass rounded-[2rem] border border-border/50 overflow-hidden flex flex-col shadow-xl">
            <div class="px-6 py-4 border-b border-border/30 bg-muted/20 flex-shrink-0">
                <h3 class="text-[10px] font-black uppercase tracking-widest text-foreground">Conversations</h3>
                <p class="text-[9px] text-muted-foreground mt-0.5"><?php echo count($inquiries); ?> thread<?php echo count($inquiries) !== 1 ? 's' : ''; ?></p>
            </div>
            <div class="flex-1 overflow-y-auto divide-y divide-border/20">
                <?php foreach ($inquiries as $inq): ?>
                <?php
                    $isActive  = (int)$inq['id'] === $active_id;
                    $isReplied = $inq['status'] === 'REPLIED';
                ?>
                <a href="?id=<?php echo $inq['id']; ?>"
                   class="block px-5 py-4 hover:bg-accent/[0.04] transition-all relative <?php echo $isActive ? 'bg-accent/[0.06] border-l-2 border-accent' : ''; ?>">
                    <?php if ($isReplied && !$isActive): ?>
                        <span class="absolute top-4 right-4 w-2 h-2 bg-green-500 rounded-full shadow-[0_0_8px_rgba(34,197,94,0.6)]"></span>
                    <?php endif; ?>

                    <div class="flex items-start gap-3">
                        <!-- Car thumbnail or icon -->
                        <div class="w-12 h-10 rounded-xl overflow-hidden border border-border/30 flex-shrink-0 bg-muted">
                            <?php if ($inq['car_image']): ?>
                                <img src="<?php echo url($inq['car_image']); ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center text-muted-foreground">
                                    <i class="fas fa-car text-xs"></i>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2 mb-0.5">
                                <h4 class="text-xs font-black text-foreground truncate <?php echo $isActive ? 'text-accent' : ''; ?>">
                                    <?php echo $inq['car_id']
                                        ? clean($inq['year'] . ' ' . $inq['make'] . ' ' . $inq['model'])
                                        : clean($inq['subject'] ?: 'General Inquiry'); ?>
                                </h4>
                                <span class="text-[8px] font-bold text-muted-foreground flex-shrink-0">
                                    <?php echo $inq['last_message_at']
                                        ? date('M d', strtotime($inq['last_message_at']))
                                        : date('M d', strtotime($inq['created_at'])); ?>
                                </span>
                            </div>
                            <p class="text-[10px] text-muted-foreground truncate leading-snug">
                                <?php echo $inq['last_message']
                                    ? clean($inq['last_message'])
                                    : clean($inq['subject'] ?: 'No messages yet'); ?>
                            </p>
                            <div class="flex items-center gap-2 mt-1.5">
                                <?php
                                $sc = ['PENDING' => 'text-yellow-500', 'REPLIED' => 'text-green-500', 'ARCHIVED' => 'text-muted-foreground'];
                                $tc = $sc[$inq['status']] ?? 'text-muted-foreground';
                                ?>
                                <span class="text-[8px] font-black uppercase tracking-widest <?php echo $tc; ?>"><?php echo $inq['status']; ?></span>
                                <span class="text-[8px] text-muted-foreground opacity-50">· <?php echo $inq['msg_count']; ?> msg<?php echo $inq['msg_count'] != 1 ? 's' : ''; ?></span>
                            </div>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Chat Panel -->
        <div class="lg:col-span-8 glass rounded-[2rem] border border-border/50 shadow-xl flex flex-col overflow-hidden">
            <?php if ($active_inquiry): ?>
                <!-- Header -->
                <div class="px-7 py-4 border-b border-border/30 bg-muted/20 flex items-center justify-between flex-shrink-0">
                    <div class="flex items-center gap-4">
                        <?php if ($active_inquiry['car_image']): ?>
                        <div class="w-12 h-10 rounded-xl overflow-hidden border border-border/30 flex-shrink-0">
                            <img src="<?php echo url($active_inquiry['car_image']); ?>" class="w-full h-full object-cover">
                        </div>
                        <?php endif; ?>
                        <div>
                            <h2 class="text-sm font-black text-foreground uppercase tracking-tight leading-none">
                                <?php echo $active_inquiry['car_id']
                                    ? clean($active_inquiry['year'] . ' ' . $active_inquiry['make'] . ' ' . $active_inquiry['model'])
                                    : clean($active_inquiry['subject'] ?: 'General Inquiry'); ?>
                            </h2>
                            <?php if ($active_inquiry['car_id']): ?>
                            <a href="<?php echo url('car-detail/' . $active_inquiry['slug']); ?>"
                               class="text-[9px] font-black text-accent uppercase tracking-widest hover:underline">
                                View Vehicle <i class="fas fa-external-link-alt text-[8px] ml-1"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                    $sc = ['PENDING' => 'bg-yellow-500/10 border-yellow-500/20 text-yellow-500', 'REPLIED' => 'bg-green-500/10 border-green-500/20 text-green-500', 'ARCHIVED' => 'bg-muted border-border text-muted-foreground'];
                    $tc = $sc[$active_inquiry['status']] ?? 'bg-muted border-border text-muted-foreground';
                    ?>
                    <span class="px-3 py-1 rounded-full border text-[8px] font-black uppercase tracking-widest <?php echo $tc; ?>">
                        <?php echo $active_inquiry['status']; ?>
                    </span>
                </div>

                <!-- Messages -->
                <div class="flex-1 overflow-y-auto px-7 py-6 space-y-5" id="chat-window">
                    <?php if (empty($messages)): ?>
                        <div class="flex flex-col items-center justify-center h-full text-center opacity-40 py-16">
                            <i class="fas fa-comment-dots text-4xl mb-3 text-muted-foreground"></i>
                            <p class="text-sm font-bold text-muted-foreground uppercase tracking-widest">Start the conversation</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $msg):
                            $isMe = ((int)$msg['sender_id'] === (int)$user['id']);
                        ?>
                        <div class="flex <?php echo $isMe ? 'justify-end' : 'justify-start'; ?> gap-3">
                            <?php if (!$isMe): ?>
                            <div class="w-8 h-8 rounded-full bg-accent/10 border border-accent/20 flex items-center justify-center text-xs font-black text-accent flex-shrink-0 mt-1">
                                <i class="fas fa-headset text-[10px]"></i>
                            </div>
                            <?php endif; ?>

                            <div class="max-w-[75%] flex flex-col gap-1.5 <?php echo $isMe ? 'items-end' : 'items-start'; ?>">
                                <div class="px-5 py-3.5 rounded-2xl text-sm font-medium leading-relaxed
                                    <?php echo $isMe
                                        ? 'bg-accent text-white rounded-tr-sm shadow-[0_4px_20px_rgba(249,115,22,0.2)]'
                                        : 'glass border border-border/50 text-foreground rounded-tl-sm'; ?>">
                                    <?php echo nl2br(clean($msg['message'])); ?>
                                </div>
                                <span class="text-[8px] font-bold text-muted-foreground opacity-60 px-1">
                                    <?php echo $isMe ? 'You' : 'Support Team'; ?>
                                    · <?php echo date('M d, H:i', strtotime($msg['created_at'])); ?>
                                </span>
                            </div>

                            <?php if ($isMe): ?>
                            <div class="w-8 h-8 rounded-full bg-muted border border-border flex items-center justify-center text-xs font-black text-muted-foreground flex-shrink-0 mt-1">
                                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Input -->
                <?php if ($active_inquiry['status'] !== 'ARCHIVED'): ?>
                <div class="px-6 py-4 border-t border-border/30 bg-muted/10 flex-shrink-0">
                    <form method="POST" class="flex items-end gap-3">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <textarea
                            name="message"
                            id="msg-input"
                            placeholder="Type your message..."
                            required
                            rows="2"
                            class="flex-1 bg-background border border-border rounded-2xl px-5 py-3.5 text-sm font-medium focus:ring-2 focus:ring-accent outline-none resize-none transition-all"
                            onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();this.form.submit();}"
                        ></textarea>
                        <button type="submit"
                                class="w-12 h-12 rounded-2xl bg-accent text-white flex items-center justify-center shadow-lg hover:scale-105 active:scale-95 transition-all flex-shrink-0">
                            <i class="fas fa-paper-plane text-sm"></i>
                        </button>
                    </form>
                    <p class="text-[9px] text-muted-foreground mt-2 ml-1">Enter to send · Shift+Enter for new line</p>
                </div>
                <?php else: ?>
                <div class="px-6 py-4 border-t border-border/30 bg-muted/10 flex-shrink-0 text-center">
                    <p class="text-[10px] font-black uppercase tracking-widest text-muted-foreground opacity-60">This conversation has been archived.</p>
                </div>
                <?php endif; ?>

            <?php else: ?>
                <!-- No thread selected -->
                <div class="flex-1 flex flex-col items-center justify-center p-12 text-center">
                    <div class="w-20 h-20 bg-accent/10 border border-accent/20 rounded-[1.5rem] flex items-center justify-center text-accent mb-6">
                        <i class="fas fa-comments text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-black text-foreground uppercase tracking-tighter mb-3">Select a Conversation</h3>
                    <p class="text-sm font-medium text-muted-foreground italic max-w-xs">
                        Choose a thread from the left to view your messages with our team.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<script>
    const chatWindow = document.getElementById('chat-window');
    if (chatWindow) chatWindow.scrollTop = chatWindow.scrollHeight;
    document.getElementById('msg-input')?.focus();
</script>

<?php
$content = ob_get_clean();
renderCustomerLayout($content, 'My Inquiries');
?>
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
