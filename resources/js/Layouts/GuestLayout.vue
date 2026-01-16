<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-slate-950 text-slate-300 relative overflow-hidden">
        
        <div class="absolute inset-0 bg-[url('https://grainy-gradients.vercel.app/noise.svg')] opacity-20 pointer-events-none"></div>
        <div class="absolute top-[-10%] left-[-10%] w-96 h-96 bg-indigo-600/20 rounded-full blur-[100px]"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-96 h-96 bg-cyan-600/20 rounded-full blur-[100px]"></div>

        <div class="relative z-10 mb-8 flex flex-col items-center">
            <Link href="/" class="group">
                <div class="flex h-20 w-20 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-600 to-cyan-500 shadow-[0_0_30px_rgba(34,211,238,0.4)] transition-all duration-300 group-hover:scale-110 group-hover:shadow-[0_0_50px_rgba(34,211,238,0.6)]">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="currentColor"
                        class="h-10 w-10 -rotate-12 text-white drop-shadow-md"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.007Zm-7.037 5.25a.75.75 0 0 1 0 1.5H2.25a.75.75 0 0 1 0-1.5h1.5Zm0 4.5a.75.75 0 0 1 0 1.5H2.25a.75.75 0 0 1 0-1.5h1.5ZM6 17.25a.75.75 0 0 1 0 1.5H2.25a.75.75 0 0 1 0-1.5H6Z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </div>
            </Link>
            
            <h1 class="mt-6 text-3xl font-black tracking-tight text-white drop-shadow-lg">
                Level Life
            </h1>
            <p class="mt-1 text-xs font-bold uppercase tracking-[0.2em] text-indigo-400">
                Productivity RPG
            </p>
        </div>

        <div
            class="w-full sm:max-w-md mt-2 px-8 py-8 bg-slate-900 border border-slate-800 shadow-2xl overflow-hidden sm:rounded-2xl relative z-10"
        >
            <slot />
        </div>
    </div>
</template>