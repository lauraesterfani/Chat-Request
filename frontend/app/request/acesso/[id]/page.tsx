'use client';

import { useEffect, useState } from 'react';
import { useParams, useRouter } from 'next/navigation';

type RequestItem = {
  id: number | string;
  subject: string;
  description: string;
  status: string;
  created_at: string;
  user: {
    name: string;
    email: string;
  };
  documents?: {
    id: number;
    name: string;
    path: string;
  }[];
};

export default function RequestAccessPage() {
  const params = useParams();
  const id = Array.isArray(params.id) ? params.id[0] : params.id;
  const router = useRouter();

  const [request, setRequest] = useState<RequestItem | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');


  useEffect(() => {
    const fetchRequest = async () => {
      try {
        const token = localStorage.getItem('jwt_token');

        if (!token) {
          router.push('/login');
          return;
        }

        const response = await fetch(
          `http://127.0.0.1:8000/api/requests/${id}`,
          {
            headers: {
              Accept: 'application/json',
              Authorization: `Bearer ${token}`,
            },
          }
        );

        if (!response.ok) {
          throw new Error('Erro ao acessar o requerimento');
        }

        const data = await response.json();
        setRequest(data);
      } catch (err: any) {
        setError(err.message || 'Erro inesperado');
      } finally {
        setLoading(false);
      }
    };

    fetchRequest();
  }, [id, router]);

  if (loading) {
    return <p className="p-8">Carregando requerimento...</p>;
  }

  if (error) {
    return <p className="p-8 text-red-600">{error}</p>;
  }

  if (!request) {
    return <p className="p-8">Requerimento não encontrado.</p>;
  }

  return (
    <div className="min-h-screen bg-slate-100 p-8">
      <h1 className="text-2xl font-bold mb-4">
        {request.subject}
      </h1>

      <div className="bg-white rounded-xl shadow p-6 space-y-4">
        <div>
          <p className="text-sm text-slate-500">Aluno</p>
          <p className="font-medium">{request.user.name}</p>
          <p className="text-sm text-slate-600">{request.user.email}</p>
        </div>

        <div>
          <p className="text-sm text-slate-500">Título</p>
          <p className="font-medium">{request.subject}</p>
        </div>

        <div>
          <p className="text-sm text-slate-500">Descrição</p>
          <p className="text-slate-700">{request.description}</p>
        </div>

        <div>
          <p className="text-sm text-slate-500">Status</p>
          <p className="capitalize font-semibold">{request.status}</p>
        </div>

        <div>
          <p className="text-sm text-slate-500">Criado em</p>
          <p>
            {new Date(request.created_at).toLocaleDateString('pt-BR')}
          </p>
        </div>

        {request.documents && request.documents.length > 0 && (
          <div>
            <p className="text-sm text-slate-500 mb-2">Documentos anexados</p>
            <ul className="list-disc list-inside space-y-1">
              {request.documents.map(doc => (
                <li key={doc.id}>
                  <a
                    href={doc.path}
                    target="_blank"
                    className="text-blue-600 hover:underline"
                  >
                    {doc.name}
                  </a>
                </li>
              ))}
            </ul>
          </div>
        )}
      </div>
    </div>
  );
}
