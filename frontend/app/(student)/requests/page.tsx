"use client";

import { useEffect, useState } from "react";
import axios from "axios";
import Link from "next/link";
import { Eye, Loader2, Plus, FileText } from "lucide-react";

const API_BASE = "http://127.0.0.1:8000/api";

export default function RequestPage() {
  const [requests, setRequests] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  // Busca a lista de requerimentos
  useEffect(() => {
    const fetchRequests = async () => {
      try {
        const token = localStorage.getItem("jwt_token");
        // Esta rota (/requests) usa o RequestController que configuramos para mostrar TUDO se for Admin
        const res = await axios.get(`${API_BASE}/requests`, {
          headers: { Authorization: `Bearer ${token}` },
        });
        setRequests(res.data || []);
      } catch (error) {
        console.error("Erro ao buscar requerimentos:", error);
      } finally {
        setLoading(false);
      }
    };

    fetchRequests();
  }, []);

  return (
    <div className="min-h-screen bg-[#F4F6F8] p-8">
      <div className="max-w-5xl mx-auto">
        
        {/* Cabeçalho */}
        <div className="flex justify-between items-center mb-8">
          <div>
            <h1 className="text-3xl font-bold text-[#0B0D3A] flex items-center gap-2">
              <FileText className="text-blue-600" /> Meus Pedidos
            </h1>
            <p className="text-gray-500 text-sm mt-1">
              Lista completa de solicitações do sistema
            </p>
          </div>

          <Link href="/requests/new" className="bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-xl font-bold flex items-center gap-2 transition-all shadow-md hover:shadow-lg">
            <Plus size={20} /> Novo Pedido
          </Link>
        </div>

        {/* Tabela de Listagem */}
        <div className="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full text-sm text-left">
              <thead className="bg-gray-50 text-gray-500 font-medium border-b border-gray-100">
                <tr>
                  <th className="px-6 py-4">Protocolo</th>
                  <th className="px-6 py-4">Assunto</th>
                  <th className="px-6 py-4">Solicitante</th>
                  <th className="px-6 py-4">Data</th>
                  <th className="px-6 py-4">Status</th>
                  <th className="px-6 py-4 text-center">Ver</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-50">
                {loading ? (
                  <tr>
                    <td colSpan={6} className="px-6 py-12 text-center">
                      <div className="flex justify-center items-center gap-2 text-gray-400">
                        <Loader2 className="animate-spin" /> Carregando...
                      </div>
                    </td>
                  </tr>
                ) : requests.length === 0 ? (
                  <tr>
                    <td colSpan={6} className="px-6 py-12 text-center text-gray-400">
                      Nenhum pedido encontrado.
                    </td>
                  </tr>
                ) : (
                  requests.map((req) => (
                    <tr key={req.id} className="hover:bg-blue-50/30 transition-colors">
                      <td className="px-6 py-4 font-mono text-xs text-gray-500">
                        {req.protocol || `#${req.id}`}
                      </td>
                      <td className="px-6 py-4 font-medium text-gray-900">
                        {req.type?.name || req.subject}
                      </td>
                      <td className="px-6 py-4 text-gray-600">
                        {/* Mostra o nome se existir, senão mostra "Eu" (caso seja o próprio aluno) */}
                        {req.user?.name || "---"}
                      </td>
                      <td className="px-6 py-4 text-gray-500">
                        {new Date(req.created_at).toLocaleDateString('pt-BR')}
                      </td>
                      <td className="px-6 py-4">
                        <span className={`px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider ${
                          req.status === 'completed' ? 'bg-green-100 text-green-700 border border-green-200' : 
                          req.status === 'analyzing' ? 'bg-amber-100 text-amber-700 border border-amber-200' : 
                          req.status === 'canceled' ? 'bg-gray-100 text-gray-600 border border-gray-200' :
                          'bg-red-50 text-red-600 border border-red-100'
                        }`}>
                          {req.status === 'pending' ? 'Pendente' : 
                           req.status === 'analyzing' ? 'Em Análise' :
                           req.status === 'completed' ? 'Deferido' : 'Indeferido'}
                        </span>
                      </td>
                      <td className="px-6 py-4 text-center">
                        <Link href={`/requests/acesso/${req.id}`} className="inline-flex items-center justify-center p-2 text-blue-600 bg-blue-50 rounded-full hover:bg-blue-600 hover:text-white transition-all">
                          <Eye size={18} />
                        </Link>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  );
}