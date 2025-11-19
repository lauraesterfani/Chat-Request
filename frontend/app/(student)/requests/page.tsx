'use client'

import { useState, useEffect } from 'react';
import { useAuth } from '@/app/context/AuthContext';
import Link from 'next/link'; // Para o botão "Solicitar Novo"

// 1. Interface para os dados do Requerimento
// (Baseado no teu RequestResource.php e no Model)
interface Requerimento {
  id: string;
  protocol: string;
  status: 'pending' | 'analyzing' | 'waiting' | 'solving' | 'completed' | 'canceled'; //
  created_at: string;
  type_request: {
    name: string;
  };
}

// 2. Componente para o "Tag" de Status
const StatusTag = ({ status }: { status: Requerimento['status'] }) => {
  const statusMap: Record<Requerimento['status'], { text: string; bg: string; textColor: string }> = {
    pending:   { text: 'Aberto',      bg: 'bg-gray-100',    textColor: 'text-gray-800' },
    analyzing: { text: 'Em Análise',  bg: 'bg-blue-100',    textColor: 'text-blue-800' },
    waiting:   { text: 'Pendente',    bg: 'bg-yellow-100',  textColor: 'text-yellow-800' },
    solving:   { text: 'Em Solução',  bg: 'bg-orange-100',  textColor: 'text-orange-800' },
    completed: { text: 'Concluído',   bg: 'bg-green-100',   textColor: 'text-green-800' },
    canceled:  { text: 'Cancelado',   bg: 'bg-red-100',     textColor: 'text-red-800' },
  };
  
  const s = statusMap[status] || statusMap.pending;

  return (
    <span className={`px-3 py-1 text-sm font-medium rounded-full ${s.bg} ${s.textColor}`}>
      {s.text}
    </span>
  );
};

export default function MeusRequerimentosPage() {
  const { token } = useAuth();
  const [requerimentos, setRequerimentos] = useState<Requerimento[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    const fetchRequerimentos = async () => {
      if (!token) return; // Espera o token estar disponível

      try {
        const response = await fetch('http://localhost:8000/api/requests', {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json',
          },
        });

        if (!response.ok) {
          throw new Error('Falha ao buscar requerimentos');
        }

        const data = await response.json();
        // A API /api/requests devolve um objeto de paginação
        setRequerimentos(data.data); 
        
      } catch (error) {
        console.error(error);
      } finally {
        setIsLoading(false);
      }
    };

    fetchRequerimentos();
  }, [token]);

  if (isLoading) {
    return <div className="text-center">A carregar requerimentos...</div>;
  }

  return (
    <div className="max-w-5xl mx-auto">
      <div className="flex justify-between items-center mb-8">
        <h1 className="text-3xl font-bold text-gray-800">Seus Requerimentos</h1>
        <Link 
          href="/chat" // Volta para o chat (ou para /requests/new)
          className="px-5 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700"
        >
          Solicitar Novo
        </Link>
      </div>

      {/* 4. Lógica para estado vazio (como no protótipo) */}
      {requerimentos.length === 0 ? (
        <div className="bg-white p-10 rounded-lg shadow-md text-center">
          <p className="text-gray-600 text-lg">
            Você ainda não solicitou nenhum requerimento.
          </p>
          <div className="mt-6 flex justify-center gap-4">
             <Link 
                href="/chat" // Volta para o chat
                className="px-6 py-3 bg-gray-200 text-gray-800 rounded-lg font-medium hover:bg-gray-300"
              >
                Voltar ao início
              </Link>
              {/* O botão "Solicitar Novo" já está no cabeçalho */}
          </div>
        </div>
      ) : (
        
        // 5. Lógica para quando EXISTEM requerimentos
        <div className="bg-white rounded-lg shadow-md overflow-hidden">
          <table className="w-full min-w-[600px]">
            <thead className="bg-gray-50">
              <tr>
                <th className="py-3 px-5 text-left text-xs font-semibold uppercase text-gray-500">Protocolo</th>
                <th className="py-3 px-5 text-left text-xs font-semibold uppercase text-gray-500">Tipo</th>
                <th className="py-3 px-5 text-left text-xs font-semibold uppercase text-gray-500">Data</th>
                <th className="py-3 px-5 text-left text-xs font-semibold uppercase text-gray-500">Status</th>
              </tr>
            </thead>
            <tbody className="text-gray-700">
              {requerimentos.map((req) => (
                <tr key={req.id} className="border-b border-gray-200 hover:bg-gray-50">
                  <td className="py-4 px-5 font-medium">{req.protocol}</td>
                  <td className="py-4 px-5">{req.type_request.name}</td>
                  <td className="py-4 px-5">
                    {/* Formata a data (ex: 2025-11-17T...) para 17/11/2025 */}
                    {new Date(req.created_at).toLocaleDateString('pt-BR')}
                  </td>
                  <td className="py-4 px-5">
                    <StatusTag status={req.status} />
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}