<script setup lang="ts">
const menus = [
  { name: 'Works', icon: 'fas fa-shield-alt', color: 'bg-rose-500', path: '/projects' },
  { name: 'Teamwork', icon: 'fas fa-users', color: 'bg-indigo-600', path: '/teamwork' },
  { name: 'Document', icon: 'fas fa-file-alt', color: 'bg-emerald-500', path: '/documents' },
  { name: 'Support', icon: 'fas fa-headset', color: 'bg-amber-400', path: '/support' },
  { name: 'Marketing', icon: 'fas fa-briefcase', color: 'bg-fuchsia-500', path: '/sales' },
  { name: 'Purchasing', icon: 'fas fa-shopping-bag', color: 'bg-purple-600', path: '/purchasing' },
  { name: 'Accounting', icon: 'fas fa-key', color: 'bg-sky-500', path: '/accounting' },
  { name: 'Financial', icon: 'fas fa-wallet', color: 'bg-slate-800', path: '/financial' },
  { name: 'Produks', icon: 'fas fa-box', color: 'bg-green-700', path: '/products' },
  { name: 'Asset', icon: 'fas fa-cube', color: 'bg-slate-500', path: '/assets' },
  { name: 'Report', icon: 'fas fa-chart-bar', color: 'bg-rose-400', path: '/reports' },
  { name: 'Setup', icon: 'fas fa-cog', color: 'bg-orange-500', path: '/settings' },
];
</script>

<template>
  <div class="w-full flex flex-col items-center p-4 md:p-10 pb-24 md:pb-10">
    <div class="w-full max-w-5xl flex flex-row justify-between items-center gap-4 mb-8">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 md:w-14 md:h-14 bg-white rounded-xl md:rounded-2xl shadow-sm p-1.5 flex items-center justify-center border border-slate-100">
           <img src="/logo-kerjapro.png" alt="Logo" class="max-w-full" />
        </div>
        <div>
          <h1 class="text-lg md:text-2xl font-black text-indigo-950 leading-none italic uppercase">KERJAPRO</h1>
          <p class="text-[8px] md:text-[10px] font-bold text-slate-400 uppercase tracking-widest">Solutions</p>
        </div>
      </div>
      
      <div class="flex items-center gap-3 bg-white px-4 py-2 rounded-xl shadow-sm border border-slate-50">
        <i class="fas fa-cloud-sun text-xl text-amber-400"></i>
        <div class="text-right">
          <p class="text-xs font-black text-slate-800 leading-none">28°C</p>
          <p class="hidden sm:block text-[9px] font-bold text-slate-400 uppercase">Bogor</p>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6 w-full max-w-5xl">
      <router-link v-for="menu in menus" :key="menu.name" :to="menu.path" 
        class="group relative flex flex-col items-center justify-center aspect-square rounded-[2rem] md:rounded-[2.5rem] transition-all duration-500 hover:-translate-y-2"
        :class="[menu.color, 'shadow-xl shadow-slate-200']">
        
        <div class="w-12 h-12 md:w-20 md:h-20 bg-white/20 rounded-2xl md:rounded-3xl flex items-center justify-center backdrop-blur-md mb-2 md:mb-4 shadow-inner">
          <i :class="menu.icon" class="text-2xl md:text-4xl text-white drop-shadow-md"></i>
        </div>

        <span class="text-white font-black uppercase text-[9px] md:text-[11px] tracking-[0.1em] md:tracking-[0.2em] italic">
          {{ menu.name }}
        </span>

        <div v-if="menu.name === 'Works'" class="absolute top-4 right-4 md:top-6 md:right-6 bg-white text-rose-500 text-[8px] md:text-[10px] font-black px-1.5 py-0.5 rounded-full shadow-lg animate-bounce">
          12
        </div>
      </router-link>
    </div>
  </div>
</template>