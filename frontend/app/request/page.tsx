'use client';

import { useEffect, useState } from 'react';
import { useAuth } from '../context/AuthContext';
import { useRouter } from 'next/navigation';

type RequestItem = {
  id: number;
  subject: string;
  status: string;
  created_at: string;
  user: {
    name: string;
    email: string;
  };
};

export default function RequestPage() {
  const { token } = useAuth(); // mesma lógica do login
  const router = useRouter(); // 👈 ADICIONADO

  const [requests, setRequests] = useState<RequestItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    if (!token) return;

    const fetchRequests = async () => {
      try {
        const response = await fetch('http://127.0.0.1:8000/api/requests', {
          headers: {
            Accept: 'application/json',
            Authorization: `Bearer ${token}`,
          },
        });

        if (!response.ok) {
          throw new Error('Erro ao buscar requerimentos');
        }

        const data = await response.json();

        if (Array.isArray(data)) {
          setRequests(data);
        } else if (Array.isArray(data.data)) {
          setRequests(data.data);
        } else {
          setRequests([]);
        }
      } catch {
        setError('Erro ao carregar requerimentos');
      } finally {
        setLoading(false);
      }
    };

    fetchRequests();
  }, [token]);

  if (loading) {
    return <p className="p-8">Carregando requerimentos...</p>;
  }

  if (error) {
    return <p className="p-8 text-red-600">{error}</p>;
  }

  return (
    <div className="min-h-screen bg-slate-100 p-8">
      <h1 className="text-2xl font-bold mb-6">
        Todos os Requerimentos
      </h1>

      {requests.length === 0 ? (
        <p>Nenhum requerimento encontrado.</p>
      ) : (
        <table className="w-full bg-white rounded-xl shadow">
          <thead>
            <tr className="bg-slate-200 text-left">
              <th className="p-3">Aluno</th>
              <th className="p-3">Email</th>
              <th className="p-3">Título</th>
              <th className="p-3">Status</th>
              <th className="p-3">Criado em</th>
            </tr>
          </thead>
          <tbody>
            {requests.map(req => (
              <tr
                key={req.id}
                className="border-t cursor-pointer hover:bg-slate-100"
                onClick={() => router.push(`/request/acesso/${req.id}`)} // 👈 AQUI ESTÁ A CHAVE
              >
                <td className="p-3">{req.user.name}</td>
                <td className="p-3">{req.user.email}</td>
                <td className="p-3">{req.subject}</td>
                <td className="p-3 capitalize">{req.status}</td>
                <td className="p-3">
                  {new Date(req.created_at).toLocaleDateString('pt-BR')}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </div>
  );
}
