<template>
  <div class="fixed inset-0 w-full h-screen flex items-center justify-center bg-[#05070a] overflow-hidden font-sans z-[999]">
    
    <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-indigo-600/20 rounded-full blur-[120px] animate-pulse"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-[30%] h-[30%] bg-blue-600/10 rounded-full blur-[100px]"></div>

    <div class="w-full max-w-[440px] px-6 relative z-10">
      <div class="bg-slate-900/40 backdrop-blur-2xl border border-white/10 p-10 rounded-[3rem] shadow-[0_20px_50px_rgba(0,0,0,0.5)] relative">
        
        <div class="absolute top-0 left-0 w-full h-[2px] bg-gradient-to-r from-transparent via-indigo-500 to-transparent opacity-50 animate-[scan_3s_infinite]"></div>

        <div class="text-center mb-10">
          <div class="inline-block p-4 rounded-3xl bg-indigo-500/10 border border-indigo-500/20 mb-6 group-hover:scale-110 transition-transform">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-indigo-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M12 8v4"/><path d="M12 16h.01"/>
            </svg>
          </div>
          <h2 class="text-white text-3xl font-black tracking-tight leading-none mb-2">
            PROJECT <span class="bg-gradient-to-r from-indigo-400 to-cyan-400 bg-clip-text text-transparent uppercase">SOLUTIONS</span>
          </h2>
          <div class="flex items-center justify-center gap-2">
            <span class="h-[1px] w-8 bg-slate-700"></span>
            <p class="text-slate-500 text-[9px] font-bold uppercase tracking-[0.3em]">Secure Auth Gate</p>
            <span class="h-[1px] w-8 bg-slate-700"></span>
          </div>
        </div>

        <form @submit.prevent="handleLogin" class="space-y-5">
          <div class="space-y-1.5 group">
            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-4 transition-colors group-focus-within:text-indigo-400">Identity Identifier</label>
            <div class="relative">
              <input 
                v-model="form.email" 
                type="email" 
                placeholder="Enter your access email" 
                class="w-full px-6 py-4 bg-slate-950/50 border border-white/5 rounded-2xl text-white text-sm font-medium outline-none focus:border-indigo-500/50 focus:ring-4 focus:ring-indigo-500/10 transition-all placeholder:text-slate-700"
                required 
              />
            </div>
          </div>

          <div class="space-y-1.5 group">
            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-4 transition-colors group-focus-within:text-indigo-400">Security Key</label>
            <input 
              v-model="form.password" 
              type="password" 
              placeholder="••••••••" 
              class="w-full px-6 py-4 bg-slate-950/50 border border-white/5 rounded-2xl text-white text-sm font-medium outline-none focus:border-indigo-500/50 focus:ring-4 focus:ring-indigo-500/10 transition-all placeholder:text-slate-700"
              required 
            />
          </div>

          <div class="pt-4">
            <button 
              type="submit" 
              :disabled="loading"
              class="group relative w-full overflow-hidden py-4 bg-white text-slate-950 rounded-2xl font-black text-xs uppercase tracking-[0.2em] transition-all hover:bg-indigo-400 hover:text-white active:scale-95 disabled:opacity-50"
            >
              <span class="relative z-10">{{ loading ? 'Establishing Connection...' : 'Initialize Session' }}</span>
              <div class="absolute inset-0 bg-gradient-to-r from-indigo-600 to-blue-600 opacity-0 group-hover:opacity-100 transition-opacity"></div>
            </button>
          </div>
        </form>

        <div class="mt-8 flex justify-between items-center px-2">
          <p class="text-[8px] text-slate-600 font-bold tracking-[0.2em] uppercase">V.4.0.1 - STABLE</p>
          <div class="flex gap-1">
            <div class="w-1 h-1 rounded-full bg-indigo-500 animate-ping"></div>
            <p class="text-[8px] text-indigo-400 font-bold tracking-[0.1em] uppercase italic">System Online</p>
          </div>
        </div>
      </div>

      <div class="mt-8 text-center">
        <p class="text-slate-600 text-[9px] font-medium tracking-widest uppercase">
          &copy; 2026 Developed by <span class="text-slate-400">Kholid Saifullah</span> for RS dr. Suyoto
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import api from '../api/axios';

const router = useRouter();
const loading = ref(false);
const form = ref({ email: '', password: '' });

const handleLogin = async () => {
  loading.value = true;
  try {
    const res = await api.post('/login', form.value);
    localStorage.setItem('token', res.data.access_token);
    localStorage.setItem('user_info', JSON.stringify(res.data.user));
    router.push('/'); 
  } catch (err: any) {
    const msg = err.response?.data?.message || 'Akses ditolak, Lid! Cek lagi email/passwordnya.';
    alert(msg);
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
@keyframes scan {
  0% { transform: translateY(0); opacity: 0; }
  50% { opacity: 0.5; }
  100% { transform: translateY(400px); opacity: 0; }
}

:deep(body) { 
  background-color: #05070a;
  margin: 0;
  padding: 0;
}

/* Custom scrollbar kalau diperlukan */
::-webkit-scrollbar {
  width: 5px;
}
::-webkit-scrollbar-track {
  background: #05070a;
}
::-webkit-scrollbar-thumb {
  background: #1e293b;
  border-radius: 10px;
}
</style>