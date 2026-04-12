<template>
  <div class="min-h-screen flex flex-col items-center p-4 md:p-8">
    <div class="w-full max-w-5xl flex flex-col md:flex-row justify-between items-center gap-6 mb-10">
      <div class="flex items-center gap-4">
        <div class="w-14 h-14 bg-white rounded-2xl shadow-sm p-2 flex items-center justify-center border border-slate-100">
           <img src="/logo-kerjapro.png" alt="Logo" class="max-w-full" />
        </div>
        <div>
          <h1 class="text-2xl font-black text-indigo-950 leading-none italic uppercase">KERJAPRO.COM</h1>
          <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-1">Management Solutions</p>
        </div>
      </div>
      
      <div class="flex items-center gap-4 bg-white px-6 py-3 rounded-2xl shadow-sm border border-slate-50">
        <i class="fas fa-cloud-sun text-2xl text-amber-400"></i>
        <div class="text-right">
          <p class="text-sm font-black text-slate-800">28°C</p>
          <p class="text-[9px] font-bold text-slate-400 uppercase">Hujan - Bogor, Puncak</p>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 w-full max-w-5xl">
      <router-link v-for="menu in menus" :key="menu.name" :to="menu.path" 
  class="group relative flex flex-col items-center justify-center aspect-square rounded-[2.5rem] transition-all duration-500 hover:-translate-y-2"
  :class="[menu.color, 'shadow-2xl shadow-slate-200 hover:shadow-indigo-200']">
  
  <div class="absolute inset-0 bg-gradient-to-br from-white/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>

  <div class="w-20 h-20 bg-white/20 rounded-3xl flex items-center justify-center backdrop-blur-md mb-4 shadow-inner group-hover:scale-110 group-hover:rotate-3 transition-all duration-500">
    <i :class="menu.icon" class="text-4xl text-white drop-shadow-md"></i>
  </div>

  <span class="text-white font-black uppercase text-[11px] tracking-[0.2em] italic drop-shadow-sm">
    {{ menu.name }}
  </span>

  <div v-if="menu.name === 'Works'" class="absolute top-6 right-6 bg-white text-rose-500 text-[10px] font-black px-2 py-0.5 rounded-full shadow-lg animate-bounce">
    12
  </div>
</router-link>
    </div>
  </div>
</template>

<script setup lang="ts">
const menus = [
  // Pastikan path '/projects' sesuai dengan di router
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