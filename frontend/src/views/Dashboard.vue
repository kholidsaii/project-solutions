<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '../api/axios'; 

const notifications = ref<any[]>([]);
const unreadCount = ref(12); // Angka dummy agar persis Figma
const showNotifications = ref(false);

// Penambahan shadow berwarna (Glow effect) agar persis Figma
const menus = [
  { name: 'Project', icon: 'fas fa-shield-alt', color: 'bg-[#FF3B30]', shadow: 'shadow-[0_10px_20px_rgba(255,59,48,0.3)]', path: '/projects' },
  { name: 'Financial', icon: 'fas fa-wallet', color: 'bg-[#2962FF]', shadow: 'shadow-[0_10px_20px_rgba(41,98,255,0.3)]', path: '/financial' },
  { name: 'Produks', icon: 'fas fa-puzzle-piece', color: 'bg-[#34C759]', shadow: 'shadow-[0_10px_20px_rgba(52,199,89,0.3)]', path: '/products' },
  { name: 'Report', icon: 'fas fa-chart-bar', color: 'bg-[#FF9F0A]', shadow: 'shadow-[0_10px_20px_rgba(255,159,10,0.3)]', path: '/reports' },
  { name: 'Support', icon: 'fas fa-headset', color: 'bg-[#E91E63]', shadow: 'shadow-[0_10px_20px_rgba(233,30,99,0.3)]', path: '/support' },
  { name: 'Setup', icon: 'fas fa-cog', color: 'bg-[#32ADE6]', shadow: 'shadow-[0_10px_20px_rgba(50,173,230,0.3)]', path: '/settings' },
];

const toggleNotifications = () => { showNotifications.value = !showNotifications.value; };

const fetchDashboardData = async () => {
  try {
    const notifRes = await api.get('/notifications'); 
    notifications.value = notifRes.data; 
    if(notifRes.data.length > 0) unreadCount.value = notifRes.data.length;
  } catch (error) { console.error(error); }
};

onMounted(() => { fetchDashboardData(); });
</script>

<template>
  <div class="min-h-screen bg-[#F4F6F9] flex flex-col justify-center items-center py-8 px-4 pl-[80px] md:pl-[100px] font-sans overflow-x-hidden">
    
    <!-- ZOOM OUT: Mengurangi max-w dari 700px menjadi 540px agar lebih proporsional -->
    <div class="w-full max-w-[540px] flex flex-col items-center">
      
      <!-- TOP WIDGET CARD -->
      <div class="bg-[#F8F9FA] rounded-[1rem] shadow-[0_4px_20px_rgba(0,0,0,0.04)] border border-slate-200 w-full mb-8 p-4 flex flex-col gap-4 relative z-50">
        
        <!-- Baris Atas: Logo & Cuaca -->
        <div class="flex justify-between items-center border-b border-slate-200 pb-3">
          <!-- Logo Kerjapro -->
          <div class="flex items-center gap-2">
            <div class="w-10 h-10 bg-white rounded-lg shadow-sm border border-slate-100 flex items-center justify-center p-1.5">
              <img src="/logo-kerjapro1.png" alt="Logo" class="w-full h-full object-contain" onerror="this.style.display='none'">
              <i class="fas fa-cube text-xl text-orange-500" v-if="false"></i>
            </div>
            <div>
              <h1 class="text-base font-black text-[#2962FF] tracking-tighter leading-none">KERJAPRO.COM</h1>
              <p class="text-[8px] text-slate-400 mt-0.5 font-bold uppercase tracking-wider">Project Management Solutions</p>
            </div>
          </div>

          <!-- Cuaca (Mirip Figma) -->
          <div class="text-right flex items-center gap-2">
             <i class="fas fa-cloud text-3xl text-slate-300"></i>
             <div class="text-left">
                <p class="text-[7px] font-bold text-slate-400 mb-0.5">14:00 WIB</p>
                <h2 class="text-xl font-light text-[#2962FF] leading-none mb-0.5">28 C</h2>
                <p class="text-[5px] text-slate-400 uppercase tracking-widest leading-tight">Monday, 21/9/2019<br>Aceh Tamiang - Aceh</p>
             </div>
          </div>
        </div>

        <!-- Baris Bawah: Ikon Aksi & Search Bar -->
        <div class="flex items-center justify-between gap-4">
          
          <!-- Ikon Kiri (Pill Box putih seperti Figma) -->
          <div class="flex items-center gap-4 bg-white shadow-sm border border-slate-100 rounded-lg px-4 py-1.5 text-[#2962FF] text-sm">
            <button class="hover:text-blue-800 transition-colors"><i class="fas fa-th"></i></button>
            <button class="hover:text-blue-800 transition-colors"><i class="fas fa-cog"></i></button>
            <button @click="toggleNotifications" class="hover:text-blue-800 transition-colors relative">
              <i class="far fa-bell"></i>
              <span v-if="unreadCount > 0" class="absolute -top-1.5 -right-2 bg-red-500 text-white text-[7px] font-bold w-3.5 h-3.5 rounded-full flex items-center justify-center border-2 border-white">
                {{ unreadCount }}
              </span>
            </button>
          </div>

          <!-- Search Bar Kanan -->
          <div class="relative flex-1">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
            <input type="text" class="w-full bg-white shadow-sm border border-slate-100 rounded-lg py-1.5 pl-8 pr-3 outline-none text-xs font-medium text-slate-600 focus:ring-1 focus:ring-[#2962FF]/20" placeholder="Search...">
          </div>
        </div>
        
        <!-- Notifikasi Popup diletakkan diluar flex box agar posisinya absolut dengan baik -->
        <div v-if="showNotifications" class="absolute top-[90px] left-4 w-64 bg-white rounded-xl shadow-xl border border-slate-100 z-[100]">
           <div class="p-3 border-b border-slate-100 bg-slate-50 rounded-t-xl">
             <h4 class="text-[10px] font-bold text-slate-700 uppercase tracking-wider">Notifications</h4>
           </div>
           <div class="p-3 text-center text-xs text-slate-500 max-h-48 overflow-y-auto">
             <p v-if="notifications.length === 0">No recent activity.</p>
             <div v-else v-for="notif in notifications" :key="notif.id" class="text-left border-b border-slate-50 py-2 last:border-0">
               <p class="font-bold text-[#2962FF] text-[10px]">{{ notif.title }}</p>
               <p class="text-[9px] mt-0.5">{{ notif.message }}</p>
             </div>
           </div>
        </div>

      </div>

      <!-- MAIN MENU GRID -->
      <div class="grid grid-cols-2 md:grid-cols-3 gap-5 w-full">
        <router-link v-for="menu in menus" :key="menu.name" :to="menu.path" 
          class="group flex flex-col items-center justify-center rounded-[1.2rem] aspect-[4/3] transition-all hover:-translate-y-1 active:scale-95 border border-white/10"
          :class="[menu.color, menu.shadow]">
          
          <i :class="menu.icon" class="text-3xl text-white mb-2 group-hover:scale-110 transition-transform duration-300"></i>
          
          <span class="text-white font-bold text-xs tracking-wider capitalize text-center">
            {{ menu.name }}
          </span>
        </router-link>
      </div>

    </div>
  </div>
</template>

<style scoped>
.overflow-y-auto::-webkit-scrollbar { width: 4px; }
.overflow-y-auto::-webkit-scrollbar-track { background: transparent; }
.overflow-y-auto::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>