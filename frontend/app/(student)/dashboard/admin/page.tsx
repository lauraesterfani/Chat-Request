"use client";

import { useState, useEffect } from "react";
import axios from "axios";
import Link from "next/link";
import { UserPlus, Users, FileText, LayoutDashboard } from "lucide-react";

const API_BASE = "http://127.0.0.1:8000/api";

export default function DashboardPage() {
  const [stats, setStats] = useState({ total_requerimentos: 0, total_usuarios: 0 });

  const fetchStats = async () => {
    try {
      const token = localStorage.getItem("jwt_token");
      const res = await axios.get(`${API_BASE}/dashboard/estatisticas`, {
        headers: { Authorization: `Bearer ${token}` },
      });
      setStats(res.data);
    } catch (error) {
      console.error("Erro ao buscar estatísticas:", error);
    }
  };

  useEffect(() => {
    fetchStats();
  }, []);

  return (
    <div className="min-h-screen flex flex-col md:flex-row bg-[#F4F6F8]">
      
      {/* Sidebar - Menu Lateral */}
      <aside className="hidden md:flex w-64 bg-[#0B0D3A] text-white flex-col p-6 shadow-lg shrink-0">
        <h1 className="text-2xl font-bold mb-4 flex items-center gap-2 truncate">
           <LayoutDashboard size={24} className="shrink-0" /> Chat Request
        </h1>
        <p className="text-xs opacity-60 mb-8 uppercase tracking-widest truncate">Painel Administrativo</p>
        
        <nav className="space-y-2 sticky top-6"> 
           <Link href="/dashboard/admin" className="flex items-center gap-3 px-4 py-3 bg-white/10 rounded-xl text-blue-100 font-medium whitespace-nowrap overflow-hidden">
             <LayoutDashboard size={18} className="shrink-0" /> 
             <span className="truncate">Visão Geral</span>
           </Link>
           <Link href="/requests" className="flex items-center gap-3 px-4 py-3 hover:bg-white/5 rounded-xl text-gray-300 transition-colors whitespace-nowrap overflow-hidden">
             <FileText size={18} className="shrink-0" /> 
             <span className="truncate">Todos Pedidos</span>
           </Link>
        </nav>
      </aside>

      {/* Conteúdo Principal */}
      <main className="flex-1 p-6 md:p-10 w-full min-w-0">
        <div className="max-w-7xl mx-auto space-y-8">
          
          <div className="mb-8">
            <h2 className="text-3xl font-bold text-[#0B0D3A]">Visão Geral</h2>
            <p className="text-gray-500">Selecione uma opção para gerenciar o sistema.</p>
          </div>

          {/* Cards de Ação */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            
            {/* Card 1: Requerimentos */}
            <Link href="/requests" className="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex items-center gap-4 hover:border-blue-400 hover:shadow-lg transition-all cursor-pointer group relative overflow-hidden">
               <div className="w-14 h-14 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform shrink-0">
                 <FileText size={28} />
               </div>
               <div className="min-w-0 flex-1">
                 <p className="text-xs text-gray-500 font-medium group-hover:text-blue-600 transition-colors uppercase tracking-wider truncate">Requerimentos</p>
                 <h3 className="text-3xl font-bold text-slate-800 truncate">{stats.total_requerimentos}</h3>
                 <span className="text-[10px] text-blue-600 font-medium mt-1 inline-block truncate w-full">Clique para ver lista →</span>
               </div>
            </Link>

            {/* Card 2: Cadastrar Staff */}
            <Link href="/signup/staff" className="group bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex items-center gap-4 hover:border-green-400 hover:shadow-lg transition-all cursor-pointer relative overflow-hidden">
               <div className="w-14 h-14 bg-green-50 text-green-600 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform shrink-0">
                 <UserPlus size={28} />
               </div>
               <div className="min-w-0 flex-1">
                 <h3 className="text-lg font-bold text-slate-800 group-hover:text-green-600 transition-colors leading-tight break-words">Cadastrar Staff</h3>
                 <p className="text-xs text-gray-400 mt-1 truncate">Adicionar administrador</p>
               </div>
            </Link>

            {/* Card 3: Equipe */}
            <Link href="/staffs" className="group bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex items-center gap-4 hover:border-purple-400 hover:shadow-lg transition-all cursor-pointer relative overflow-hidden">
               <div className="w-14 h-14 bg-purple-50 text-purple-600 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform shrink-0">
                 <Users size={28} />
               </div>
               <div className="min-w-0 flex-1">
                 <h3 className="text-lg font-bold text-slate-800 group-hover:text-purple-600 transition-colors leading-tight break-words">Equipe</h3>
                 <p className="text-xs text-gray-400 mt-1 truncate">Ver lista de administradores</p>
               </div>
            </Link>
          </div>

        </div>
      </main>
    </div>
  );
}