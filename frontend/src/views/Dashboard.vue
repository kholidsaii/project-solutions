<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../api/axios'; 

const router = useRouter();

// State Profil Pengguna (Reactive)
const userName = ref('Loading...');
const userJobTitle = ref('-');
const userPhone = ref('-');
const userAvatar = ref('https://ui-avatars.com/api/?name=User&background=f1f5f9&color=333');
const qrCodeId = ref('000000');

// State Media Sosial
const userFacebook = ref('');
const userTwitter = ref('');
const userInstagram = ref('');
const userLinkedin = ref('');

// Menu Grid (Tetap sesuai desain awal)
const menus = [
  { name: 'Works', icon: 'fas fa-shield-alt', color: 'bg-[#FF453A]', shadow: 'shadow-[0_10px_20px_rgba(255,69,58,0.35)]', path: '/projects' },
  { name: 'Financial', icon: 'fas fa-wallet', color: 'bg-[#0A7AFF]', shadow: 'shadow-[0_10px_20px_rgba(10,122,255,0.35)]', path: '/financial' },
  { name: 'Produks', icon: 'fas fa-puzzle-piece', color: 'bg-[#32D74B]', shadow: 'shadow-[0_10px_20px_rgba(50,215,75,0.35)]', path: '/products' },
  { name: 'Report', icon: 'fas fa-chart-bar', color: 'bg-[#FF9F0A]', shadow: 'shadow-[0_10px_20px_rgba(255,159,10,0.35)]', path: '/reports' },
  { name: 'Support', icon: 'fas fa-headset', color: 'bg-[#E024CE]', shadow: 'shadow-[0_10px_20px_rgba(224,36,206,0.35)]', path: '/support' },
];

const fetchDashboardData = async () => {
  try {
    // 1. Ambil info login dari localStorage (Gunakan 'user' atau 'user_info' sesuai auth Anda)
    const rawUser = localStorage.getItem('user') || localStorage.getItem('user_info');
    const localUser = JSON.parse(rawUser || '{}');

    if (localUser.email) {
      // 2. Tarik data lengkap dari API Users berdasarkan email agar akurat
      const usersRes = await api.get('/users', { params: { tag_search: localUser.email } });
      const usersList = usersRes.data.data || usersRes.data;
      
      // Cari profil yang emailnya pas banget
      const myProfile = usersList.find((u: any) => u.email === localUser.email);

      if (myProfile) {
         // 3. Mapping data dari API ke State Dashboard
         userName.value = myProfile.name;
         userJobTitle.value = myProfile.position || (myProfile.role === 'super_admin' ? 'Owner' : 'Staff');
         userPhone.value = myProfile.phone || 'No Contact';
         qrCodeId.value = myProfile.account_number ? myProfile.account_number.slice(-7) : myProfile.id.toString().padStart(6, '0');

         userFacebook.value = myProfile.facebook || '';
         userTwitter.value = myProfile.twitter || '';
         userInstagram.value = myProfile.instagram || '';
         userLinkedin.value = myProfile.linkedin || '';
         
         const baseUrl = (import.meta.env.VITE_API_URL || 'http://localhost:8000').replace('/api', '');
         userAvatar.value = myProfile.avatar_url 
            ? `${baseUrl}/uploads/${myProfile.avatar_url}` 
            : `https://ui-avatars.com/api/?name=${encodeURIComponent(myProfile.name)}&background=f1f5f9&color=0A7AFF`;
      }
    }
  } catch (error) { 
    console.error("Dashboard Error:", error); 
  }
};

const logout = () => {
  localStorage.clear();
  router.push('/login');
};

onMounted(() => { 
  fetchDashboardData(); 
});
</script>

<template>
  <div class="min-h-screen bg-[#F4F6F9] flex flex-col justify-start items-center pt-[12vh] pb-8 px-4 pl-[80px] md:pl-[100px] font-sans overflow-x-hidden">
    
    <div class="w-full max-w-[620px] flex flex-col items-center">
      
      <div class="flex items-center gap-4 mb-5 self-start pl-2">
        <i class="fas fa-cloud-showers-heavy text-4xl text-[#B0BEC5]"></i>
        <div class="text-left flex flex-col justify-center">
          <p class="text-[9px] font-bold text-slate-500 mb-0.5">14:00 WIB</p>
          <h2 class="text-2xl font-medium text-[#2962FF] leading-none mb-1">28 C</h2>
          <p class="text-[7px] text-slate-500 uppercase tracking-widest leading-tight">
            Monday, 21/9/2019<br>Hujan &nbsp; Aceh Tamiang - Aceh
          </p>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-[0_8px_30px_rgba(0,0,0,0.04)] border border-slate-200 w-full mb-8 p-5 flex items-center justify-between gap-4 relative z-10">
        
        <button @click="logout" class="absolute top-4 right-5 text-slate-200 hover:text-rose-500 transition-all hover:scale-110 active:scale-95" title="Logout">
          <i class="fas fa-sign-out-alt text-xl"></i>
        </button>

        <div class="flex items-center gap-5">
          <div class="w-[72px] h-[72px] rounded-full overflow-hidden shadow-sm border-[3px] border-slate-100 bg-white">
            <img :src="userAvatar" alt="Avatar" class="w-full h-full object-cover">
          </div>
          
          <div class="flex flex-col">
            <div class="flex items-center gap-1.5 mb-0.5">
              <h1 class="text-[17px] font-bold text-[#0A7AFF] tracking-tight leading-none">{{ userName }}</h1>
              <i class="fas fa-check-circle text-[15px] text-[#32D74B]"></i>
            </div>
            <p class="text-[11px] text-slate-500 mb-1 font-semibold uppercase">{{ userJobTitle }}</p>
            <p class="text-[11px] text-slate-600 font-bold mb-3">{{ userPhone }}</p>
            
            <div class="flex items-center gap-3">
              <button class="bg-[#FFB900] hover:bg-[#F59E0B] transition-colors text-white text-[9px] font-bold px-3 py-1.5 rounded-full shadow-sm whitespace-nowrap">
                Super Account
              </button>
              
              <div class="flex items-center gap-3 text-sm">
                <a v-if="userFacebook" :href="userFacebook" target="_blank" class="text-[#0A7AFF] hover:scale-110 transition-transform"><i class="fab fa-facebook-f"></i></a>
                <i v-else class="fab fa-facebook-f text-slate-100"></i>

                <a v-if="userTwitter" :href="userTwitter" target="_blank" class="text-[#38BDF8] hover:scale-110 transition-transform"><i class="fab fa-twitter"></i></a>
                <i v-else class="fab fa-twitter text-slate-100"></i>

                <a v-if="userInstagram" :href="userInstagram" target="_blank" class="text-[#E1306C] hover:scale-110 transition-transform"><i class="fab fa-instagram"></i></a>
                <i v-else class="fab fa-instagram text-slate-100"></i>

                <a v-if="userLinkedin" :href="userLinkedin" target="_blank" class="text-[#1D4ED8] hover:scale-110 transition-transform"><i class="fab fa-linkedin-in"></i></a>
                <i v-else class="fab fa-linkedin-in text-slate-100"></i>
              </div>
            </div>
          </div>
        </div>

        <div class="flex flex-col items-center gap-2 border-l border-slate-100 pl-8 pr-4 h-full justify-center mt-2">
          <i class="fas fa-qrcode text-5xl text-slate-600"></i>
          <p class="text-[11px] text-slate-500 font-bold tracking-wider text-center uppercase">{{ qrCodeId }}</p>
        </div>

      </div>

      <div class="grid grid-cols-5 gap-3.5 w-full">
        <router-link v-for="menu in menus" :key="menu.name" :to="menu.path" 
          class="group flex flex-col items-center justify-center rounded-[1rem] aspect-[4/3] transition-all hover:-translate-y-1 active:scale-95 border border-white/20"
          :class="[menu.color, menu.shadow]">
          
          <i :class="menu.icon" class="text-2xl text-white mb-1.5 group-hover:scale-110 transition-transform duration-300"></i>
          
          <span class="text-white font-bold text-[10px] tracking-wide capitalize text-center">
            {{ menu.name }}
          </span>
        </router-link>
      </div>

    </div>
  </div>
</template>