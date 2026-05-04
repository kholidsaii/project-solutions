<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '../api/axios'; 

// ==========================================
// 1. STATE MANAGEMENT
// ==========================================
const user = ref({
  name: 'Loading...',
  role: 'User',
  avatar: '',
  accountType: 'Individual Account',
  stats: { profit: '0', works: '0' }
});

const notifications = ref<any[]>([]);
const unreadCount = ref(0);
const isLoading = ref(false);
const showNotifications = ref(false);

const menus = [
  { name: 'Projects', icon: 'fas fa-tasks', color: 'bg-[#EF4444]', path: '/projects' },
  { name: 'Teamwork', icon: 'fas fa-users', color: 'bg-[#3B82F6]', path: '/teamwork' },
  { name: 'Financial', icon: 'fas fa-wallet', color: 'bg-[#10B981]', path: '/financial' },
  { name: 'Products', icon: 'fas fa-puzzle-piece', color: 'bg-[#FBBF24]', path: '/products' },
  { name: 'Reports', icon: 'fas fa-chart-bar', color: 'bg-[#F43F5E]', path: '/reports' },
  { name: 'Setup', icon: 'fas fa-cog', color: 'bg-[#F97316]', path: '/settings' },
];

const toggleNotifications = () => {
  showNotifications.value = !showNotifications.value;
};

// ==========================================
// 2. API ACTIONS
// ==========================================
const fetchDashboardData = async () => {
  try {
    const storedUser = JSON.parse(localStorage.getItem('user') || '{}');
    user.value.name = storedUser.name || 'User';
    user.value.role = storedUser.role || 'Staff';
    user.value.avatar = `https://ui-avatars.com/api/?name=${user.value.name}&background=2E3A8C&color=fff`;
    user.value.accountType = storedUser.company_id ? 'Corporate Account' : 'Individual Account';

    const res = await api.get('/finance/consolidated');
    if (res.data) {
      const profitVal = (res.data.net_profit || 0) / 1000000;
      user.value.stats.profit = profitVal > 0 ? `+${profitVal.toFixed(1)}M` : `${profitVal.toFixed(1)}M`;
    }

    const notifRes = await api.get('/notifications'); 
    notifications.value = notifRes.data; 
    unreadCount.value = notifRes.data.length;
  } catch (error) {
    console.error("Gagal mengambil data Dashboard:", error);
  }
};

const fetchWorksData = async () => {
  isLoading.value = true;
  try {
    const res = await api.get('/works/stats');
    if (res.data && res.data.summary) {
      user.value.stats.works = res.data.summary.total?.toString() || '0';
    }
  } catch (error) {
    console.error("Gagal ambil data stats:", error);
  } finally {
    setTimeout(() => { isLoading.value = false; }, 500);
  }
};

onMounted(() => {
  fetchDashboardData();
  fetchWorksData();
});
</script>

<template>
  <div class="min-h-screen bg-[#F8FAFC] flex flex-col items-center pt-2 px-4 md:pl-28 transition-all overflow-x-hidden text-slate-900 font-sans">
    
    <div class="w-full max-w-[380px]">
      
      <!-- USER CARD (Logo di kiri atas logo user) -->
      <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 mb-4 overflow-hidden mt-2">
        
        <!-- Cover Photo -->
        <div class="h-16 w-full bg-gradient-to-r from-[#2E3A8C] to-indigo-500 relative flex items-center px-4">
          
          <!-- BRANDING DI KIRI (Berada di atas area avatar nanti) -->
          <!-- <div class="flex flex-col opacity-90"> 
            <div class="flex items-center gap-1.5">
              <img src="/logo-kerjapro1.png" alt="Logo" class="w-3 filter brightness-0 invert" />
              <h1 class="text-[10px] font-black text-white tracking-tighter uppercase leading-none">KERJAPRO.COM</h1>
            </div>
            <p class="text-[5px] text-indigo-100 font-bold uppercase tracking-[0.2em] mt-0.5">Management System</p>
          </div> -->

          <!-- Account Type Badge (Kanan Atas) -->
          <div class="absolute top-3 right-3 flex items-center gap-2">
             <!-- <span class="text-[8px] font-black text-white/30 italic mr-1">28°C</span> -->
             <span class="px-2 py-0.5 rounded-full text-[5px] font-black uppercase tracking-widest backdrop-blur-md border border-white/10 bg-white/10 text-white">
               {{ user.accountType }}
             </span>
          </div>
        </div>

        <div class="px-4 pb-4 relative">
          <!-- Avatar & Shortcut -->
          <div class="relative -mt-7 mb-2 flex justify-between items-end">
            <div class="relative">
              <img :src="user.avatar" class="w-14 h-14 rounded-2xl border-4 border-white shadow-lg object-cover bg-white" />
              <div class="absolute bottom-1 right-0.5 w-3 h-3 bg-emerald-500 border-2 border-white rounded-full shadow-sm"></div>
            </div>
            
            <router-link to="/teamwork" class="bg-slate-50 hover:bg-[#2E3A8C] text-[#2E3A8C] hover:text-white px-3 py-1.5 rounded-xl border border-slate-100 transition-all flex items-center gap-1.5 active:scale-95 shadow-sm mb-0.5">
              <i class="fas fa-users-cog text-[9px]"></i>
              <span class="text-[8px] font-black uppercase tracking-widest leading-none">Detail</span>
            </router-link>
          </div>

          <!-- User Info & Stats -->
          <div class="flex justify-between items-center mt-1">
            <div class="min-w-0 text-left">
              <h2 class="text-[11px] font-black text-slate-800 leading-none truncate flex items-center gap-1">
                {{ user.name }}
                <i v-if="user.accountType === 'Corporate Account'" class="fas fa-check-circle text-blue-500 text-[9px]"></i>
              </h2>
              <p class="text-[8px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">{{ user.role }}</p>
            </div>

            <div class="flex gap-3 border-l border-slate-100 pl-3">
              <div class="text-center">
                <p class="text-[5px] text-slate-400 font-bold uppercase mb-0">Profit</p>
                <p class="text-[10px] font-black text-emerald-600 tracking-tighter leading-tight">{{ user.stats.profit }}</p>
              </div>
              <div class="text-center">
                <p class="text-[5px] text-slate-400 font-bold uppercase mb-0">Works</p>
                <p class="text-[10px] font-black text-blue-600 tracking-tighter leading-tight">{{ user.stats.works }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- SEARCH & NOTIF BAR -->
      <div class="flex items-center gap-2 mb-3 relative">
        <div class="flex items-center bg-white shadow-sm rounded-xl p-0.5 border border-slate-50 flex-shrink-0">
           <button class="w-7 h-7 flex items-center justify-center text-[#2E3A8C] hover:bg-slate-50 rounded-lg"><i class="fas fa-th-large text-[10px]"></i></button>
           <button @click="toggleNotifications" class="w-7 h-7 flex items-center justify-center text-[#2E3A8C] relative border-l border-slate-100 ml-0.5 pl-0.5 transition-all active:scale-90">
             <i class="far fa-bell text-[10px]"></i>
             <span v-if="unreadCount > 0" class="absolute top-1.5 right-1.5 w-1.5 h-1.5 bg-red-500 rounded-full border border-white animate-pulse"></span>
           </button>
        </div>

        <!-- POP-UP NOTIFIKASI -->
        <div v-if="showNotifications" class="absolute top-10 left-0 w-72 bg-white rounded-2xl shadow-2xl border border-slate-100 z-[100] animate-in slide-in-from-top-2 duration-300">
          <div class="p-4 border-b border-slate-50 flex justify-between items-center">
            <h4 class="text-[10px] font-black uppercase text-[#2E3A8C] tracking-widest">Recent Activity</h4>
            <span class="text-[8px] bg-indigo-50 text-indigo-600 px-2 py-0.5 rounded-full font-bold">{{ unreadCount }} New</span>
          </div>
          <div class="max-h-64 overflow-y-auto p-2 custom-scroll">
            <div v-if="notifications.length === 0" class="py-10 text-center opacity-30">
              <i class="fas fa-bell-slash text-xl mb-2"></i>
              <p class="text-[8px] font-bold uppercase">No recent activity</p>
            </div>
            <div v-for="notif in notifications" :key="notif.id" class="p-3 hover:bg-slate-50 rounded-xl transition-colors cursor-pointer group">
              <div class="flex items-start gap-3">
                <div class="w-7 h-7 rounded-lg bg-indigo-50 flex items-center justify-center text-[#2E3A8C] flex-shrink-0 group-hover:bg-[#2E3A8C] group-hover:text-white transition-colors">
                  <i class="fas fa-bolt text-[9px]"></i>
                </div>
                <div class="min-w-0">
                  <p class="text-[10px] font-black text-slate-800 leading-tight uppercase truncate">{{ notif.title }}</p>
                  <p class="text-[9px] text-slate-500 leading-snug mt-0.5 line-clamp-2 italic">{{ notif.message }}</p>
                  <p class="text-[7px] text-indigo-400 font-bold mt-1 uppercase tracking-tighter">{{ notif.time }}</p>
                </div>
              </div>
            </div>
          </div>
          <div class="p-3 bg-slate-50 rounded-b-2xl text-center">
            <button class="text-[8px] font-black text-slate-400 hover:text-[#2E3A8C] uppercase tracking-widest">View All Logs</button>
          </div>
        </div>

        <div class="relative flex-1">
          <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-[8px]"></i>
          <input type="text" class="w-full bg-white shadow-sm border border-slate-100 rounded-xl py-2 pl-8 outline-none text-[10px] font-bold text-slate-600 focus:ring-2 ring-indigo-50" placeholder="Search...">
        </div>
      </div>

      <!-- GRID MENU -->
      <div class="grid grid-cols-3 gap-2 w-full pb-8">
        <router-link v-for="menu in menus" :key="menu.name" :to="menu.path" 
          class="group flex flex-col items-center justify-center aspect-square rounded-[1.75rem] transition-all shadow-sm hover:translate-y-[-2px] active:scale-95 border-b-2 border-black/10"
          :class="menu.color">
          <div class="w-8 h-8 bg-white/20 rounded-xl flex items-center justify-center mb-1.5 shadow-inner">
            <i :class="menu.icon" class="text-xs text-white"></i>
          </div>
          <span class="text-white font-black text-[8px] tracking-tight uppercase text-center px-1 leading-none">
            {{ menu.name }}
          </span>
        </router-link>
      </div>

    </div>
  </div>
</template>

<style scoped>
.custom-scroll::-webkit-scrollbar { width: 4px; }
.custom-scroll::-webkit-scrollbar-track { background: transparent; }
.custom-scroll::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
:deep(body) { overflow-x: hidden; }
</style>