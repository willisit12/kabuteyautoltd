<?php
/**
 * pages/about.php
 * About Page Template
 */

$pageTitle = 'About Us';
include_once __DIR__ . '/../includes/layout/header.php';
?>

<section class="relative pt-32 pb-24 overflow-hidden bg-background transition-colors duration-500">
    <!-- Background Accents -->
    <div class="absolute inset-0 z-0 opacity-20 dark:opacity-10 pointer-events-none">
        <div class="absolute top-0 right-[-10%] w-[600px] h-[600px] bg-accent/15 rounded-full blur-[140px]"></div>
        <div class="absolute bottom-0 left-[-10%] w-[500px] h-[500px] bg-accent/10 rounded-full blur-[120px]"></div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <!-- Hero Narrative -->
        <div class="text-center mb-24 reveal-section">
            <span class="inline-block text-accent font-black tracking-[0.3em] uppercase text-[10px] mb-4">
                Established 2012
            </span>
            <h1 class="text-5xl md:text-9xl font-black text-foreground mb-6 tracking-tighter uppercase leading-[0.9]">
                The Williams <br> 
                <span class="text-gradient">Heritage.</span>
            </h1>
            <p class="text-muted-foreground text-lg md:text-2xl max-w-3xl mx-auto leading-relaxed italic border-x-2 border-accent/20 px-8 py-2">
                Merging the precision of engineering with the soul of a private collection. We don't just sell vehicles; we guardian the cinematic experience of driving.
            </p>
        </div>
        
        <!-- Story Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 lg:gap-24 items-center mb-32 reveal-section">
            <div class="relative group">
                <div class="absolute inset-0 bg-accent/20 rounded-[3rem] blur-2xl group-hover:bg-accent/30 transition-all duration-700 -z-10"></div>
                <img src="https://images.unsplash.com/photo-1562141989-c5c79ac8f576?auto=format&fit=crop&q=80&w=1200" 
                     alt="Our Showroom" 
                     class="rounded-[3rem] shadow-2xl border border-border/50 transition-transform duration-1000 group-hover:scale-[1.02]">
            </div>
            <div class="space-y-8">
                <h2 class="text-3xl md:text-5xl font-black text-foreground tracking-tighter uppercase">Our <span class="text-accent underline decoration-accent/20 underline-offset-8">Philosophy</span></h2>
                <div class="prose prose-accent dark:prose-invert max-w-none text-muted-foreground text-lg leading-relaxed font-medium">
                    <p>Williams Auto was born in the heart of Toronto from a singular vision: to redefine the pre-owned luxury market. Our founder, Marcus Williams, believed that acquiring a masterpiece of engineering should be as memorable as the first time you turn the ignition.</p>
                    <p>Every vehicle in our collection undergoes a rigorous "Cinematic Standard" inspection. We look beyond the mechanical, analyzing the aesthetic soul and the heritage of ownership to ensure that every car we deliver is a legacy in motion.</p>
                    <p class="italic text-foreground font-bold">"We are not in the business of machines. We are in the business of the exhilaration they inspire."</p>
                </div>
            </div>
        </div>

        <!-- Team Showcase -->
        <div class="mb-32 reveal-section">
            <div class="flex flex-col md:flex-row justify-between items-end mb-16 gap-6">
                <div>
                    <h2 class="text-4xl md:text-6xl font-black text-foreground tracking-tighter uppercase leading-none">The <span class="text-gradient">Visionaries.</span></h2>
                    <p class="text-muted-foreground mt-4 text-lg font-medium">The experts behind the collection.</p>
                </div>
                <div class="h-[1px] flex-1 bg-border/20 hidden md:block mb-4 ml-8"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
                <!-- CEO/Founder -->
                <div class="group/team flex flex-col">
                    <div class="relative rounded-[2.5rem] overflow-hidden mb-8 aspect-[4/5] border border-border/50 shadow-xl">
                        <img src="https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&q=80&w=800" class="w-full h-full object-cover grayscale group-hover/team:grayscale-0 transition-all duration-700 scale-105 group-hover/team:scale-100">
                        <div class="absolute inset-0 bg-gradient-to-t from-background/90 via-transparent to-transparent opacity-0 group-hover/team:opacity-100 transition-opacity duration-500"></div>
                        <div class="absolute bottom-6 left-6 opacity-0 group-hover/team:opacity-100 transition-all duration-500 translate-y-4 group-hover/team:translate-y-0">
                            <div class="flex gap-4 text-foreground">
                                <a href="#" class="hover:text-accent"><i class="fab fa-linkedin-in"></i></a>
                                <a href="#" class="hover:text-accent"><i class="fab fa-twitter"></i></a>
                            </div>
                        </div>
                    </div>
                    <h4 class="text-2xl font-black text-foreground tracking-tight mb-2">Marcus Williams</h4>
                    <p class="text-accent font-black uppercase tracking-widest text-xs">Founder & Chief Curator</p>
                </div>

                <!-- Sales Director -->
                <div class="group/team flex flex-col">
                    <div class="relative rounded-[2.5rem] overflow-hidden mb-8 aspect-[4/5] border border-border/50 shadow-xl">
                        <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&q=80&w=800" class="w-full h-full object-cover grayscale group-hover/team:grayscale-0 transition-all duration-700 scale-105 group-hover/team:scale-100">
                        <div class="absolute inset-0 bg-gradient-to-t from-background/90 via-transparent to-transparent opacity-0 group-hover/team:opacity-100 transition-opacity duration-500"></div>
                        <div class="absolute bottom-6 left-6 opacity-0 group-hover/team:opacity-100 transition-all duration-500 translate-y-4 group-hover/team:translate-y-0">
                            <div class="flex gap-4 text-foreground">
                                <a href="#" class="hover:text-accent"><i class="fab fa-linkedin-in"></i></a>
                                <a href="#" class="hover:text-accent"><i class="fab fa-instagram"></i></a>
                            </div>
                        </div>
                    </div>
                    <h4 class="text-2xl font-black text-foreground tracking-tight mb-2">Elena Rossi</h4>
                    <p class="text-accent font-black uppercase tracking-widest text-xs">Director of Acquisitions</p>
                </div>

                <!-- Master Tech -->
                <div class="group/team flex flex-col">
                    <div class="relative rounded-[2.5rem] overflow-hidden mb-8 aspect-[4/5] border border-border/50 shadow-xl">
                        <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&q=80&w=800" class="w-full h-full object-cover grayscale group-hover/team:grayscale-0 transition-all duration-700 scale-105 group-hover/team:scale-100">
                        <div class="absolute inset-0 bg-gradient-to-t from-background/90 via-transparent to-transparent opacity-0 group-hover/team:opacity-100 transition-opacity duration-500"></div>
                        <div class="absolute bottom-6 left-6 opacity-0 group-hover/team:opacity-100 transition-all duration-500 translate-y-4 group-hover/team:translate-y-0">
                            <div class="flex gap-4 text-foreground">
                                <a href="#" class="hover:text-accent"><i class="fab fa-linkedin-in"></i></a>
                            </div>
                        </div>
                    </div>
                    <h4 class="text-2xl font-black text-foreground tracking-tight mb-2">Jameson Blake</h4>
                    <p class="text-accent font-black uppercase tracking-widest text-xs">Master Performance Technician</p>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 mb-12 reveal-section">
            <div class="glass p-10 rounded-[2.5rem] border border-border/50 text-center hover:scale-105 transition-all duration-500 group">
                <div class="text-5xl font-black text-foreground mb-3 tracking-tighter group-hover:text-accent transition-colors">500+</div>
                <div class="text-muted-foreground font-black uppercase tracking-[0.2em] text-[10px]">Collection Pieces Sold</div>
            </div>
            <div class="glass p-10 rounded-[2.5rem] border border-border/50 text-center hover:scale-105 transition-all duration-500 group">
                <div class="text-5xl font-black text-foreground mb-3 tracking-tighter group-hover:text-accent transition-colors">99%</div>
                <div class="text-muted-foreground font-black uppercase tracking-[0.2em] text-[10px]">Enthusiast Satisfaction</div>
            </div>
            <div class="glass p-10 rounded-[2.5rem] border border-border/50 text-center hover:scale-105 transition-all duration-500 group">
                <div class="text-5xl font-black text-foreground mb-3 tracking-tighter group-hover:text-accent transition-colors">12+</div>
                <div class="text-muted-foreground font-black uppercase tracking-[0.2em] text-[10px]">Years of Excellence</div>
            </div>
            <div class="glass p-10 rounded-[2.5rem] border border-border/50 text-center hover:scale-105 transition-all duration-500 group">
                <div class="text-5xl font-black text-foreground mb-3 tracking-tighter group-hover:text-accent transition-colors">GTA</div>
                <div class="text-muted-foreground font-black uppercase tracking-[0.2em] text-[10px]">Premier Concierge</div>
            </div>
        </div>
    </div>
</section>

<?php include_once __DIR__ . '/../includes/layout/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const tl = gsap.timeline({ defaults: { ease: "power3.out" }});

        tl.from(".reveal-section", {
            y: 30,
            opacity: 0,
            duration: 0.8,
            stagger: 0.15,
            clearProps: "all"
        });
    });
</script>
