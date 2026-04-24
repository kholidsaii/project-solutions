<script setup lang="ts">
const menus = [
  { name: 'Works', icon: 'fas fa-shield-alt', color: 'bg-[#EF4444]', path: '/projects' },
  { name: 'Teamwork', icon: 'fas fa-users', color: 'bg-[#3B82F6]', path: '/teamwork' },
  { name: 'Document', icon: 'fas fa-file-alt', color: 'bg-[#22C55E]', path: '/documents' },
  { name: 'Support', icon: 'fas fa-headset', color: 'bg-[#FBBF24]', path: '/support' },
  { name: 'Marketing', icon: 'fas fa-briefcase', color: 'bg-[#D946EF]', path: '/sales' },
  { name: 'Purchasing', icon: 'fas fa-shopping-bag', color: 'bg-[#8B5CF6]', path: '/purchasing' },
  { name: 'Accounting', icon: 'fas fa-key', color: 'bg-[#0EA5E9]', path: '/accounting' },
  { name: 'Financial', icon: 'fas fa-wallet', color: 'bg-[#1E293B]', path: '/financial' },
  { name: 'Produks', icon: 'fas fa-puzzle-piece', color: 'bg-[#15803D]', path: '/products' },
  { name: 'Asset', icon: 'fas fa-exclamation-triangle', color: 'bg-[#64748B]', path: '/assets' },
  { name: 'Report', icon: 'fas fa-chart-bar', color: 'bg-[#F43F5E]', path: '/reports' },
  { name: 'Setup', icon: 'fas fa-cog', color: 'bg-[#F97316]', path: '/settings' },
];
</script>

<template>
  <div class="min-h-screen bg-[#F8FAFC] flex flex-col items-center pt-6 pr-4 pl-20 md:pl-32 transition-all">
    
    <div class="w-full max-w-[420px]">
      
      <div class="flex justify-between items-start mb-5 gap-2">
        <div class="flex items-center gap-2 min-w-0"> <div class="flex-shrink-0">
             <img src="/logo-kerjapro1.png" alt="Logo" class="w-4" />
          </div>
          <div class="truncate">
            <h1 class="text-sm md:text-base font-black text-[#2E3A8C] tracking-tight leading-none uppercase">KERJAPRO.COM</h1>
            <p class="text-[6px] md:text-[7px] text-slate-400 font-medium italic mt-0.5 uppercase tracking-tighter">Project Management</p>
          </div>
        </div>

        <div class="flex items-center gap-1 md:gap-2 text-[#2E3A8C] flex-shrink-0">
          <i class="fas fa-cloud-sun-rain text-sm md:text-xl opacity-20"></i>
          <div class="leading-tight text-right">
            <div class="flex items-baseline justify-end gap-1">
              <span class="text-[5px] md:text-[6px] font-bold opacity-50">14:00</span>
              <span class="text-xs md:text-base font-black">28 C</span>
            </div>
          </div>
        </div>
      </div>

      <div class="flex items-center gap-2 mb-5">
        <div class="flex items-center bg-white shadow-sm rounded-lg p-0.5 border border-slate-50 flex-shrink-0">
           <button class="w-6 h-6 md:w-7 md:h-7 flex items-center justify-center text-blue-800"><i class="fas fa-th-large text-[9px] md:text-[10px]"></i></button>
           <button class="w-6 h-6 md:w-7 md:h-7 flex items-center justify-center text-blue-800 relative border-l border-slate-100 ml-1 pl-1">
             <i class="far fa-bell text-[9px] md:text-[10px]"></i>
           </button>
        </div>
        <div class="relative flex-1">
          <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-300 text-[9px]"></i>
          <input type="text" class="w-full bg-white shadow-sm border border-slate-50 rounded-lg py-1 pl-8 outline-none text-[10px]" placeholder="Search...">
        </div>
      </div>

      <div class="grid grid-cols-3 md:grid-cols-4 gap-2 md:gap-2.5 w-full pb-10">
        <router-link v-for="menu in menus" :key="menu.name" :to="menu.path" 
          class="group flex flex-col items-center justify-center aspect-square rounded-[0.8rem] md:rounded-[0.9rem] transition-all shadow-sm"
          :class="menu.color">
          
          <div class="w-6 h-6 md:w-7 md:h-7 bg-white/20 rounded-md flex items-center justify-center mb-1">
            <i :class="menu.icon" class="text-xs md:text-sm text-white"></i>
          </div>

          <span class="text-white font-bold text-[6px] md:text-[7px] tracking-tight uppercase text-center px-1">
            {{ menu.name }}
          </span>
        </router-link>
      </div>

    </div>
  </div>
</template>