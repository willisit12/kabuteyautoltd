<?php
/**
 * includes/component/chat-concierge.php
 * High-fidelity, Guazi-inspired Concierge Chat Component
 */
function renderChatConcierge($car_id = null) {
    $user = isLoggedIn() ? getUserInfo() : null;
    $car = null;
    if ($car_id) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM cars WHERE id = ?");
        $stmt->execute([$car_id]);
        $car = $stmt->fetch();
    }
    ?>
    <div x-data="{ 
        chatOpen: false, 
        message: '', 
        email: '',
        loading: false,
        step: 'welcome', // welcome, inquiry, leadgen
        async submitLead() {
            if (!this.email.trim()) return;
            this.loading = true;
            // Simulate lead storage or redirect to register
            setTimeout(() => {
                window.location.href = '<?php echo url('register'); ?>?email=' + encodeURIComponent(this.email);
            }, 800);
        },
        async dispatchInquiry() {
            if (!this.message.trim()) return;
            this.loading = true;
            try {
                const res = await fetch('<?php echo url('api/inquire'); ?>', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken
                    },
                    body: JSON.stringify({ 
                        car_id: <?php echo $car_id ?: 0; ?>,
                        subject: '<?php echo $car ? "Inquiry: " . clean($car['year'] . " " . $car['make'] . " " . $car['model']) : "General Inquiry"; ?>',
                        message: this.message 
                    })
                });
                const data = await res.json();
                if (data.status === 'success') {
                    window.dispatchEvent(new CustomEvent('notify', { 
                        detail: { message: 'Intelligence dispatched to concierge.', type: 'success' } 
                    }));
                    this.message = '';
                    this.chatOpen = false;
                }
            } catch (e) { console.error(e); }
            finally { this.loading = false; }
        }
    }" 
    @open-concierge.window="chatOpen = true"
    class="fixed bottom-8 right-8 z-[1000]">
        
        <!-- Float Trigger -->
        <button 
            @click="chatOpen = !chatOpen"
            class="w-16 h-16 bg-accent rounded-full flex items-center justify-center text-white shadow-[0_15px_40px_rgba(249,115,22,0.4)] hover:scale-110 active:scale-95 transition-all group relative"
        >
            <i class="fas fa-comment-dots text-2xl group-hover:rotate-12 transition-transform"></i>
            <span class="absolute -top-1 -right-1 w-5 h-5 bg-white text-accent rounded-full flex items-center justify-center text-[10px] font-black border-2 border-accent animate-bounce">1</span>
        </button>

        <!-- Chat Window -->
        <div 
            x-show="chatOpen"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-10 scale-90"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-10 scale-90"
            @click.away="chatOpen = false"
            data-lenis-prevent
            class="fixed bottom-24 inset-x-4 sm:inset-x-auto sm:right-8 sm:w-[500px] md:w-[400px] h-[600px] glass rounded-[3rem] border border-white/20 shadow-2xl flex flex-col overflow-hidden max-w-[95vw] z-[1001]"
            style="display: none;"
        >
            <!-- Header -->
            <div class="p-6 bg-accent/10 border-b border-white/10 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-accent rounded-xl flex items-center justify-center text-white shadow-lg">
                        <i class="fas fa-car text-lg"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-foreground tracking-tight uppercase text-sm"><?php echo SITE_NAME; ?></h3>
                        <div class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span>
                            <span class="text-[8px] font-black uppercase tracking-widest text-muted-foreground opacity-60">Concierge Active</span>
                        </div>
                    </div>
                </div>
                <button @click="chatOpen = false" class="text-foreground/40 hover:text-foreground transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Thread Area -->
            <div class="flex-1 overflow-y-auto p-6 space-y-6 custom-scrollbar bg-card/10">
                <div class="text-center">
                    <span class="text-[10px] font-black uppercase tracking-widest text-muted-foreground opacity-40"><?php echo date('H:i'); ?></span>
                </div>

                <!-- Welcome Message -->
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 bg-accent/20 rounded-lg flex items-center justify-center text-accent shrink-0">
                        <i class="fas fa-robot text-xs"></i>
                    </div>
                    <div class="bg-white dark:bg-card p-4 rounded-2xl rounded-tl-none shadow-sm border border-border/10 max-w-[85%]">
                        <p class="text-sm font-bold text-foreground leading-relaxed">Hi there! Welcome to <?php echo SITE_NAME; ?> Concierge.</p>
                        <p class="text-[11px] font-medium text-muted-foreground mt-2 leading-relaxed italic">We currently have limited-run inventory and private collections ready for acquisition. How can I assist your search today?</p>
                    </div>
                </div>

                <?php if (!$user): ?>
                    <!-- Guest Lead Card -->
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 bg-accent/20 rounded-lg flex items-center justify-center text-accent shrink-0">
                            <i class="fas fa-shield-heart text-xs"></i>
                        </div>
                        <div class="bg-white dark:bg-card p-6 rounded-2xl rounded-tl-none shadow-lg border border-accent/20 max-w-[90%]">
                            <h4 class="text-xs font-black text-foreground uppercase tracking-widest mb-3">Elite Consultation</h4>
                            <p class="text-[11px] font-medium text-muted-foreground leading-relaxed mb-4">Leave your email or <a href="<?php echo url('register'); ?>" class="text-accent hover:underline">join the circle</a> to initiate a one-on-one consultation with our experts.</p>
                            
                            <div class="relative">
                                <input 
                                    x-model="email"
                                    type="email" 
                                    placeholder="Enter your email..." 
                                    class="w-full bg-muted/50 border border-border/50 rounded-xl px-4 py-3 text-[11px] outline-none focus:ring-2 focus:ring-accent transition-all pl-10"
                                >
                                <i class="fas fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-[10px] text-muted-foreground cursor-default"></i>
                                <button 
                                    @click="submitLead()"
                                    :disabled="!email.trim() || loading"
                                    class="absolute right-1 top-1 bottom-1 px-4 bg-accent text-white rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-accent-600 transition-all disabled:opacity-50"
                                >
                                    <span x-show="!loading">Submit</span>
                                    <i x-show="loading" class="fas fa-circle-notch animate-spin"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Asset Context -->
                <?php if ($car): ?>
                    <div class="flex items-start gap-3 justify-end">
                        <div class="bg-accent text-white p-4 rounded-2xl rounded-tr-none shadow-lg max-w-[85%] border border-accent/20">
                            <p class="text-[10px] font-black uppercase tracking-widest opacity-60 mb-1">Asset Intelligence</p>
                            <p class="text-sm font-bold tracking-tight"><?php echo clean($car['year'] . ' ' . $car['make'] . ' ' . $car['model']); ?></p>
                        </div>
                        <div class="w-8 h-8 bg-accent rounded-lg flex items-center justify-center text-white shrink-0 shadow-md">
                            <i class="fas fa-car text-xs"></i>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div class="px-6 py-4 flex gap-2 overflow-x-auto no-scrollbar border-t border-white/10 bg-card/20 shrink-0">
                <a href="https://wa.me/yournumber" target="_blank" class="flex-shrink-0 flex items-center gap-2 px-4 py-2 bg-white dark:bg-card border border-border/50 rounded-xl hover:bg-accent hover:text-white transition-all group">
                    <i class="fab fa-whatsapp text-green-500 group-hover:text-white transition-colors"></i>
                    <span class="text-[9px] font-black uppercase tracking-widest">WhatsApp</span>
                </a>
                <button @click="step = 'inquiry'" class="flex-shrink-0 flex items-center gap-2 px-4 py-2 bg-white dark:bg-card border border-border/50 rounded-xl hover:bg-accent hover:text-white transition-all group">
                    <i class="fas fa-envelope text-accent group-hover:text-white transition-colors"></i>
                    <span class="text-[9px] font-black uppercase tracking-widest">Inquire Now</span>
                </button>
                <a href="<?php echo url('cars'); ?>" class="flex-shrink-0 flex items-center gap-2 px-4 py-2 bg-white dark:bg-card border border-border/50 rounded-xl hover:bg-accent hover:text-white transition-all group">
                    <i class="fas fa-search text-muted-foreground group-hover:text-white transition-colors"></i>
                    <span class="text-[9px] font-black uppercase tracking-widest">Showroom</span>
                </a>
            </div>

            <!-- Input Area -->
            <div class="p-6 pt-2 bg-white dark:bg-card/30 border-t border-white/5 shrink-0">
                <div class="relative group">
                    <textarea 
                        x-model="message"
                        @keydown.enter.prevent="if(message.trim()) dispatchInquiry()"
                        placeholder="<?php echo $user ? 'Begin your correspondence...' : 'Unlock full intelligence to message...'; ?>" 
                        <?php echo !$user ? 'disabled' : ''; ?>
                        class="w-full bg-muted/30 border border-border/20 rounded-2xl p-5 pr-14 text-sm font-medium outline-none focus:ring-2 focus:ring-accent transition-all resize-none min-h-[80px] <?php echo !$user ? 'cursor-not-allowed opacity-50' : ''; ?>"
                    ></textarea>
                    
                    <div class="absolute bottom-4 right-4 flex items-center gap-3">
                        <button 
                            @click="dispatchInquiry()"
                            :disabled="!message.trim() || loading || !<?php echo $user ? 'true' : 'false'; ?>"
                            class="w-10 h-10 bg-accent text-white rounded-xl flex items-center justify-center shadow-lg hover:scale-110 active:scale-95 transition-all disabled:opacity-50 disabled:hover:scale-100"
                        >
                            <i x-show="!loading" class="fas fa-paper-plane text-xs"></i>
                            <i x-show="loading" class="fas fa-circle-notch animate-spin text-xs"></i>
                        </button>
                    </div>
                </div>
                <?php if (!$user): ?>
                    <p class="text-[8px] font-black uppercase tracking-[0.2em] text-center mt-4 text-muted-foreground opacity-40">Identity Authentication Required</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}
