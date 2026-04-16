<!-- Navbar -->
<nav class="glass-panel rounded-2xl px-4 sm:px-6 py-4 " x-data="{ mobileMenu: false }">
    <div class="flex items-center justify-between gap-4">
        <a href="#" class="flex items-center gap-3">
            <img src="https://techwave.asia/storage/services/light-logo-142x75.png" alt="BlueGlass Logo"
                class="h-10 rounded-xl">
        </a>

        <div class="hidden lg:flex items-center gap-8 text-sm font-medium text-blue-50/85">
            <a href="#" class="hover:text-white transition">Home</a>
            <a href="#" class="hover:text-white transition">Services</a>
            <a href="#" class="hover:text-white transition">Tools</a>
            <a href="#" class="hover:text-white transition">Blogs</a>
            <a href="#" class="hover:text-white transition">About</a>
            <a href="#" class="hover:text-white transition">Contact</a>
        </div>

        <div class="hidden lg:flex items-center gap-3">
            <a href="#"
                class="px-5 py-2.5 rounded-full glass-chip text-blue-50 font-medium hover:bg-white/20 transition">Sign
                In</a>
            <a href="#"
                class="px-5 py-2.5 rounded-full bg-gradient-to-r from-blue-500 to-sky-400 text-white font-semibold shadow-lg shadow-blue-500/25 hover:scale-[1.02] transition">
                Get Started
            </a>
        </div>

        <button @click="mobileMenu = !mobileMenu"
            class="lg:hidden w-11 h-11 rounded-xl glass-chip flex items-center justify-center text-white">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </div>

    <!-- Mobile Menu -->
    <div x-show="mobileMenu" x-transition class="lg:hidden mt-4 pt-4 border-t border-white/10">
        <div class="flex flex-col gap-3 text-sm text-blue-50/85">
            <a href="#" class="px-4 py-3 rounded-xl glass-soft">Home</a>
            <a href="#" class="px-4 py-3 rounded-xl glass-soft">Services</a>
            <a href="#" class="px-4 py-3 rounded-xl glass-soft">Solutions</a>
            <a href="#" class="px-4 py-3 rounded-xl glass-soft">About</a>
            <a href="#" class="px-4 py-3 rounded-xl glass-soft">Contact</a>
            <div class="grid grid-cols-2 gap-3 pt-2">
                <a href="#" class="text-center px-4 py-3 rounded-xl glass-soft font-medium">Sign In</a>
                <a href="#"
                    class="text-center px-4 py-3 rounded-xl bg-gradient-to-r from-blue-500 to-sky-400 font-semibold">Get
                    Started</a>
            </div>
        </div>
    </div>
</nav>
