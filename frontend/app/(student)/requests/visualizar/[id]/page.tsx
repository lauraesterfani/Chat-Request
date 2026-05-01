'use client';

import { useEffect, useState } from 'react';
import { useParams, useRouter } from 'next/navigation';
import { useAuth } from '../../../../context/AuthContext';
import { FileText, ExternalLink, Loader2, ChevronLeft } from 'lucide-react';

export default function RequestViewPage() {
  const { token } = useAuth();
  const params = useParams();
  const router = useRouter();
  const [request, setRequest] = useState<any>(null);
  const [loading, setLoading] = useState(true);

  const statusColors: any = {
    pending: "bg-blue-100 text-blue-700 border-blue-200",
    analyzing: "bg-amber-100 text-amber-700 border-amber-200",
    completed: "bg-emerald-100 text-emerald-700 border-emerald-200",
    canceled: "bg-red-50 text-red-600 border-red-100",
  };

  const statusLabels: any = {
    pending: "Pendente",
    analyzing: "Em Análise",
    completed: "Deferido",
    canceled: "Indeferido",
  };

  useEffect(() => {
    const fetchRequest = async () => {
      try {
        const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/requests/${params.id}`, {
          headers: { 'Authorization': `Bearer ${token}` },
        });
        const data = await response.json();
        setRequest(data);
      } catch (err) {
        console.error('Erro ao carregar requerimento', err);
      } finally {
        setLoading(false);
      }
    };

    if (params?.id && token) {
      fetchRequest();
    }
  }, [params, token]);

  if (loading) {
    return (
      <div className="flex flex-col items-center justify-center min-h-screen gap-4">
        <div className="w-10 h-10 border-4 border-emerald-200 border-t-emerald-600 rounded-full animate-spin" />
        <p className="text-slate-500 animate-pulse font-medium text-sm">Carregando detalhes...</p>
      </div>
    );
  }

  if (!request) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-[#F4F6F8]">
        <div className="text-center">
          <p className="text-gray-500 font-medium">Requerimento não encontrado.</p>
          <button onClick={() => router.back()} className="mt-4 text-emerald-700 font-bold hover:underline">Voltar</button>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-[#F4F6F8] py-10 px-4">
      <div className="max-w-4xl mx-auto bg-white rounded-3xl shadow-sm overflow-hidden border border-gray-100">
        
        {/* Header Verde Petróleo */}
        <div className="bg-[#004d40] p-6 text-white flex justify-between items-center">
          <button onClick={() => router.back()} className="p-2 hover:bg-white/10 rounded-full transition">
            <ChevronLeft size={24} />
          </button>
          <h1 className="text-xl font-bold truncate px-4">{request.type?.name || request.subject}</h1>
          <span className={`px-4 py-1 rounded-full text-[10px] font-black border uppercase tracking-widest ${statusColors[request.status] || 'bg-gray-100'}`}>
            {statusLabels[request.status] || request.status}
          </span>
        </div>

        <div className="p-8 grid grid-cols-1 md:grid-cols-3 gap-8">
          
          {/* Conteúdo Principal */}
          <div className="md:col-span-2 space-y-8">
            <section>
              <h3 className="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 ml-1">Descrição do Requerimento</h3>
              <div className="bg-gray-50 p-6 rounded-2xl text-gray-700 border border-gray-100 leading-relaxed text-sm">
                {request.description || "Nenhuma descrição fornecida."}
              </div>
            </section>

            {/* Observação da Coordenação (Se houver) */}
            {request.observation && (
              <section>
                <h3 className="text-xs font-bold text-emerald-700 uppercase tracking-wider mb-3 ml-1">Resposta da Coordenação</h3>
                <div className="bg-emerald-50/50 p-6 rounded-2xl text-gray-700 border border-emerald-100 leading-relaxed text-sm italic">
                  {request.observation}
                </div>
              </section>
            )}

            <section>
              <h3 className="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 ml-1">Documentos Anexados</h3>
              {request.documents?.length > 0 ? (
                <div className="grid grid-cols-1 gap-4">
                  {request.documents.map((doc: any) => {
                    const fileUrl = `http://127.0.0.1:8000/storage/${doc.path.replace("public/", "")}`;
                    const isImage = /\.(jpg|jpeg|png|webp|gif)$/i.test(doc.path);
                    return (
                      <div key={doc.id} className="group flex flex-col gap-3 p-4 border border-gray-100 rounded-2xl hover:border-emerald-200 hover:bg-emerald-50/30 transition-all bg-white shadow-sm">
                        {isImage ? (
                          <a href={fileUrl} target="_blank" rel="noopener noreferrer" className="block cursor-zoom-in overflow-hidden rounded-xl bg-gray-50 border border-gray-100">
                            <img src={fileUrl} alt={doc.name} className="w-full h-48 object-contain hover:scale-105 transition-transform duration-500" />
                          </a>
                        ) : (
                          <a href={fileUrl} target="_blank" rel="noopener noreferrer" className="flex items-center gap-3 p-6 bg-gray-50 rounded-xl border-2 border-dashed border-gray-200 hover:border-emerald-300 hover:bg-emerald-50 transition cursor-pointer">
                            <FileText size={32} className="text-emerald-600" />
                            <span className="text-sm font-bold text-gray-500 uppercase tracking-tighter">Visualizar Documento PDF</span>
                          </a>
                        )}
                        <div className="flex justify-between items-center px-1">
                          <p className="text-sm font-bold text-gray-700 truncate max-w-[250px]" title={doc.name}>{doc.name}</p>
                          <a href={fileUrl} target="_blank" rel="noopener noreferrer" className="flex items-center gap-1 text-xs font-black text-emerald-700 hover:underline">
                            ABRIR <ExternalLink size={14} />
                          </a>
                        </div>
                      </div>
                    );
                  })}
                </div>
              ) : (
                <p className="text-sm text-gray-400 italic ml-1">Nenhum anexo encontrado.</p>
              )}
            </section>
          </div>

          {/* Sidebar Lateral */}
          <div className="space-y-6">
            <div className="bg-gray-50 p-6 rounded-2xl border border-gray-100">
              <h3 className="text-[10px] font-black text-gray-400 uppercase mb-4 tracking-widest">Meus Dados</h3>
              <div className="space-y-1">
                <p className="text-sm font-bold text-gray-800">{request.user?.name}</p>
                <p className="text-xs text-gray-500 truncate mb-4">{request.user?.email}</p>
              </div>
              <div className="text-xs text-gray-600 space-y-3 border-t border-gray-200 pt-4 mt-2">
                <p><strong>Matrícula:</strong> {request.user?.matricula || "---"}</p>
                <p><strong>Curso:</strong> {request.user?.course?.name || "---"}</p>
              </div>
            </div>

            <div className="bg-emerald-50/30 p-6 rounded-2xl border border-emerald-100 shadow-sm text-center">
              <p className="text-[10px] font-black text-emerald-800 uppercase tracking-widest">Protocolo</p>
              <p className="text-lg font-mono font-bold text-emerald-900 mt-1">
                {request.protocol || `#${request.id.substring(0, 8)}`}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}