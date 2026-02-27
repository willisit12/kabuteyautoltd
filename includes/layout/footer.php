    <!-- Footer -->
    <footer class="bg-background text-foreground py-24 border-t border-border relative overflow-hidden transition-colors duration-500">
        <div class="absolute top-0 left-0 w-full h-[1px] bg-gradient-to-r from-transparent via-accent/30 to-transparent"></div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-16 mb-20">
                <div class="col-span-1 md:col-span-1">
                    <a href="<?php echo url(); ?>" class="inline-block mb-8">
                        <img src="<?php echo url('images/logo-main.png'); ?>" alt="<?php echo SITE_NAME; ?>" class="h-12 object-contain dark:brightness-110 brightness-90">
                    </a>
                    <p class="text-muted-foreground leading-relaxed font-medium max-w-xs transition-colors">
                        <?php echo SITE_TAGLINE; ?>. <br>Toronto's heritage for automotive excellence since 2012.
                    </p>
                </div>
               
                <div>
                    <h4 class="text-foreground font-bold uppercase tracking-widest text-xs mb-8">Navigation</h4>
                    <ul class="space-y-4">
                        <li><a href="<?php echo url(); ?>" class="text-muted-foreground hover:text-accent transition-colors duration-300">Showroom</a></li>
                        <li><a href="<?php echo url('cars'); ?>" class="text-muted-foreground hover:text-accent transition-colors duration-300">Inventory</a></li>
                        <li><a href="<?php echo url('about'); ?>" class="text-muted-foreground hover:text-accent transition-colors duration-300">Our Story</a></li>
                        <li><a href="<?php echo url('contact'); ?>" class="text-muted-foreground hover:text-accent transition-colors duration-300">Concierge</a></li>
                    </ul>
                </div>
               
                <div>
                    <h4 class="text-foreground font-bold uppercase tracking-widest text-xs mb-8">Connect</h4>
                    <ul class="space-y-4 text-muted-foreground">
                        <li class="flex items-center gap-4 group">
                            <span class="w-10 h-10 rounded-full bg-muted flex items-center justify-center group-hover:bg-accent/20 group-hover:text-accent transition-all">
                                <i class="fas fa-phone-alt text-sm"></i>
                            </span>
                            <span class="font-medium">+233202493547</span>
                        </li>
                        <li class="flex items-center gap-4 group">
                            <span class="w-10 h-10 rounded-full bg-muted flex items-center justify-center group-hover:bg-accent/20 group-hover:text-accent transition-all">
                                <i class="fas fa-envelope text-sm"></i>
                            </span>
                            <span class="font-medium">concierge@williamsauto.com</span>
                        </li>
                    </ul>
                </div>
               
                <div>
                    <h4 class="text-foreground font-bold uppercase tracking-widest text-xs mb-8">Social Landscape</h4>
                    <div class="flex space-x-4">
                        <a href="#" class="w-12 h-12 rounded-2xl bg-muted flex items-center justify-center border border-border hover:border-accent/50 hover:bg-accent/10 hover:text-accent transition-all duration-500 group text-foreground shadow-sm">
                            <i class="fab fa-instagram text-xl transition-transform group-hover:scale-110"></i>
                        </a>
                        <a href="#" class="w-12 h-12 rounded-2xl bg-muted flex items-center justify-center border border-border hover:border-accent/50 hover:bg-accent/10 hover:text-accent transition-all duration-500 group text-foreground shadow-sm">
                            <i class="fab fa-facebook-f text-xl transition-transform group-hover:scale-110"></i>
                        </a>
                        <a href="#" class="w-12 h-12 rounded-2xl bg-muted flex items-center justify-center border border-border hover:border-accent/50 hover:bg-accent/10 hover:text-accent transition-all duration-500 group text-foreground shadow-sm">
                            <i class="fab fa-youtube text-xl transition-transform group-hover:scale-110"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="flex flex-col md:flex-row justify-between items-center pt-12 border-t border-border/10 gap-8">
                <p class="text-muted-foreground text-sm italic transition-colors">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Engineered by Passion.</p>
                <div class="flex gap-10 text-xs font-bold uppercase tracking-tighter text-muted-foreground/60 hover:text-muted-foreground transition-colors">
                    <a href="#" class="hover:text-accent transition-colors">Privacy Protocol</a>
                    <a href="#" class="hover:text-accent transition-colors">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
