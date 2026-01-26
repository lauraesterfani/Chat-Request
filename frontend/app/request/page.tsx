"use client";

import { useState, useEffect } from "react";
import { useRouter } from "next/navigation";
import Link from "next/link";
import { useAuth } from "@/app/context/AuthContext"; // Importe seu contexto se tiver, ou use localStorage direto

const API_BASE = "http://127.0.0.1:8000/api";

export default function RequestListPage() {
  const router = useRouter();
  const [requests, setRequests] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [filter, setFilter] = useState("all");

  useEffect(() => {
    const fetchRequests = async () => {
      const token = localStorage.getItem("jwt_token");
      if (!token) {
          router.push("/login");
          return;
      }

      try {
        const res = await fetch(`${API_BASE}/requests`, {
          headers: { Authorization: `Bearer ${token}` }
        });
        
        if (res.ok) {
          setRequests(await res.json());
        }
      } catch (error) {
        console.error("Erro ao buscar pedidos");
      } finally {
        setLoading(false);
      }
    };

    fetchRequests();
  }, [router]);

  // Função para filtrar na tela
  const filteredRequests = requests.filter(req => 
    filter === "all" ? true : req.status === filter
  );

  const getStatusBadge = (status: string) => {
    const map: any = {
      pending: "bg-yellow-100 text-yellow-800 border-yellow-200",
      analyzing: "bg-blue-100 text-blue-800 border-blue-200",
      completed: "bg-green-100 text-green-800 border-green-200",
      rejected: "bg-red-100 text-red-800 border-red-200"
    };
    const labels: any = {
        pending: "Pendente", analyzing: "Em Análise", completed: "Deferido", rejected: "Indeferido"
    };
    return (
      <span className={`px-3 py-1 rounded-full text-xs font-bold border ${map[status] || "bg-gray-100"}`}>
        {labels[status] || status}
      </span>
    );
  };

  return (
    <div className="min-h-screen bg-gray-50 p-6 font-sans">
      <div className="max-w-6xl mx-auto">
        
        {/* Cabeçalho */}
        <div className="flex justify-between items-center mb-8">
            <div className="flex items-center gap-3">
                <button onClick={() => router.back()} className="p-2 hover:bg-gray-200 rounded-full transition">
                    ⬅️
                </button>
                <div>
                    <h1 className="text-2xl font-bold text-gray-800">Central de Requerimentos</h1>
                    <p className="text-gray-500 text-sm">Gerencie todas as solicitações recebidas.</p>
                </div>
            </div>
            
            {/* Botão Novo (Tarefa 3 - Atalho Rápido) */}
            <Link href="/requests/new" className="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-bold shadow-md transition flex items-center gap-2">
                + Novo Pedido
            </Link>
        </div>

        {/* Filtros */}
        <div className="flex gap-2 mb-6 overflow-x-auto pb-2">
            {['all', 'pending', 'analyzing', 'completed', 'rejected'].map((f) => (
                <button 
                    key={f}
                    onClick={() => setFilter(f)}
                    className={`px-4 py-2 rounded-full text-sm font-medium transition-all ${
                        filter === f ? 'bg-green-600 text-white shadow-md' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'
                    }`}
                >
                    {f === 'all' ? 'Todos' : f.charAt(0).toUpperCase() + f.slice(1)}
                </button>
            ))}
        </div>

        {/* Lista */}
        {loading ? (
            <div className="text-center py-20 text-gray-400">Carregando solicitações...</div>
        ) : filteredRequests.length === 0 ? (
            <div className="text-center py-20 bg-white rounded-2xl border border-gray-100 shadow-sm">
                <p className="text-gray-500">Nenhum requerimento encontrado.</p>
            </div>
        ) : (
            <div className="grid gap-4">
                {filteredRequests.map((req) => (
                    <Link key={req.id} href={`/request/acesso/${req.id}`}>
                        <div className="bg-white p-5 rounded-xl border border-gray-100 shadow-sm hover:shadow-md hover:border-green-200 transition-all cursor-pointer flex justify-between items-center group">
                            <div className="flex items-center gap-4">
                                <div className={`w-12 h-12 rounded-full flex items-center justify-center text-xl font-bold
                                    ${req.user?.role === 'admin' ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-600'}
                                `}>
                                    {req.user?.name?.charAt(0) || '?'}
                                </div>
                                <div>
                                    <h3 className="font-bold text-gray-800 group-hover:text-green-700 transition">{req.subject}</h3>
                                    <p className="text-xs text-gray-500">
                                        {req.user?.name} • Matrícula: {req.user?.matricula}
                                    </p>
                                    <p className="text-xs text-gray-400 mt-1">
                                        Protocolo: {req.protocol || 'N/A'} • {new Date(req.created_at).toLocaleDateString()}
                                    </p>
                                </div>
                            </div>
                            <div className="flex flex-col items-end gap-2">
                                {getStatusBadge(req.status)}
                                <span className="text-xs text-gray-400">Tipo: {req.type?.name}</span>
                            </div>
                        </div>
                    </Link>
                ))}
            </div>
        )}
      </div>
    </div>
  );
}