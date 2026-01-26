"use client";

import { useState, useEffect } from "react";
import { useParams, useRouter } from "next/navigation";

const API_BASE = "http://127.0.0.1:8000/api";

export default function RequestDetailsPage() {
  const { id } = useParams();
  const router = useRouter();
  const [request, setRequest] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [updating, setUpdating] = useState(false);
  
  // Campo de Observação para o Admin escrever
  const [observation, setObservation] = useState("");

  // Pega o usuário logado para saber se pode editar
  const [currentUser, setCurrentUser] = useState<any>(null);

  useEffect(() => {
    const fetchData = async () => {
        const token = localStorage.getItem("jwt_token");
        if (!token) return router.push("/login");

        // 1. Pega dados do usuário logado (simulado via localStorage ou decode do token seria ideal)
        // Aqui vamos assumir que se ele acessou a rota admin, ele tem permissão, mas o backend valida.
        // Vamos fazer um fetch rápido no /me se necessário, mas para agilizar:
        try {
             const meRes = await fetch(`${API_BASE}/me`, { headers: { Authorization: `Bearer ${token}` } });
             if(meRes.ok) setCurrentUser(await meRes.json());

             const res = await fetch(`${API_BASE}/requests/${id}`, {
                 headers: { Authorization: `Bearer ${token}` }
             });
             if (res.ok) {
                 const data = await res.json();
                 setRequest(data);
                 setObservation(data.observation || "");
             } else {
                 alert("Requerimento não encontrado ou acesso negado.");
                 router.back();
             }
        } catch (err) {
            console.error(err);
        } finally {
            setLoading(false);
        }
    };
    fetchData();
  }, [id, router]);

  const handleUpdateStatus = async (newStatus: string) => {
    if(!confirm(`Tem certeza que deseja mudar o status para: ${newStatus}?`)) return;
    
    setUpdating(true);
    const token = localStorage.getItem("jwt_token");

    try {
        const res = await fetch(`${API_BASE}/requests/${id}`, {
            method: "PUT", // Ou PATCH, dependendo da rota resource do Laravel
            headers: {
                "Content-Type": "application/json",
                Authorization: `Bearer ${token}`
            },
            body: JSON.stringify({ status: newStatus, observation })
        });

        if (res.ok) {
            const updated = await res.json();
            setRequest({ ...request, status: newStatus, observation });
            alert("✅ Status atualizado com sucesso!");
        } else {
            alert("❌ Erro ao atualizar status.");
        }
    } catch (error) {
        alert("Erro de conexão.");
    } finally {
        setUpdating(false);
    }
  };

  if (loading || !request) return <div className="p-10 text-center">Carregando detalhes...</div>;

  const isAdmin = ["admin", "staff", "cradt"].includes(currentUser?.role);

  return (
    <div className="min-h-screen bg-gray-50 py-10 px-4">
        <div className="max-w-4xl mx-auto bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
            
            {/* Header com Status */}
            <div className="bg-gray-800 p-8 text-white flex justify-between items-start">
                <div>
                    <button onClick={() => router.back()} className="text-gray-300 hover:text-white mb-4 flex items-center gap-1 text-sm">
                        ⬅ Voltar
                    </button>
                    <h1 className="text-2xl font-bold">{request.type?.name}</h1>
                    <p className="opacity-80 mt-1">Protocolo: {request.protocol}</p>
                </div>
                <div className={`px-4 py-2 rounded-lg font-bold text-sm uppercase tracking-wide
                    ${request.status === 'completed' ? 'bg-green-500 text-white' : 
                      request.status === 'rejected' ? 'bg-red-500 text-white' : 
                      'bg-yellow-500 text-black'}
                `}>
                    {request.status}
                </div>
            </div>

            <div className="p-8 grid grid-cols-1 md:grid-cols-3 gap-8">
                
                {/* Coluna Principal: Dados do Pedido */}
                <div className="md:col-span-2 space-y-6">
                    <div>
                        <h3 className="text-sm font-bold text-gray-500 uppercase mb-1">Assunto</h3>
                        <p className="text-gray-900 font-medium text-lg">{request.subject}</p>
                    </div>

                    <div>
                        <h3 className="text-sm font-bold text-gray-500 uppercase mb-1">Descrição</h3>
                        <div className="bg-gray-50 p-4 rounded-xl text-gray-700 leading-relaxed border border-gray-100">
                            {request.description}
                        </div>
                    </div>

                    {/* Área de Anexos */}
                    <div>
                        <h3 className="text-sm font-bold text-gray-500 uppercase mb-3">Documentos Anexados</h3>
                        {request.documents && request.documents.length > 0 ? (
                            <div className="grid grid-cols-1 gap-3">
                                {request.documents.map((doc: any) => (
                                    <a 
                                        key={doc.id} 
                                        href={`http://127.0.0.1:8000/storage/${doc.path}`} 
                                        target="_blank"
                                        className="flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:bg-blue-50 hover:border-blue-300 transition group"
                                    >
                                        <div className="bg-red-100 p-2 rounded text-red-600">📄</div>
                                        <div className="flex-1 min-w-0">
                                            <p className="text-sm font-medium text-gray-700 truncate group-hover:text-blue-700">{doc.name}</p>
                                            <p className="text-xs text-gray-400">Clique para visualizar</p>
                                        </div>
                                    </a>
                                ))}
                            </div>
                        ) : (
                            <p className="text-sm text-gray-400 italic">Nenhum anexo enviado.</p>
                        )}
                    </div>
                </div>

                {/* Coluna Lateral: Dados do Aluno & Ações Admin */}
                <div className="space-y-6">
                    
                    {/* Card do Aluno */}
                    <div className="bg-gray-50 p-5 rounded-xl border border-gray-200">
                        <h3 className="text-xs font-bold text-gray-500 uppercase mb-3">Dados do Solicitante</h3>
                        <div className="flex items-center gap-3 mb-3">
                            <div className="w-10 h-10 bg-green-200 rounded-full flex items-center justify-center text-green-800 font-bold">
                                {request.user?.name.charAt(0)}
                            </div>
                            <div>
                                <p className="text-sm font-bold text-gray-800">{request.user?.name}</p>
                                <p className="text-xs text-gray-500">{request.user?.email}</p>
                            </div>
                        </div>
                        <div className="text-xs text-gray-600 space-y-1 border-t border-gray-200 pt-3">
                            <p><strong>Matrícula:</strong> {request.user?.matricula || "N/A"}</p>
                            <p><strong>Curso:</strong> {request.user?.course?.name || "N/A"}</p>
                            <p><strong>Telefone:</strong> {request.user?.phone || "N/A"}</p>
                        </div>
                    </div>

                    {/* PAINEL DE AÇÃO DA CRADT (SÓ APARECE SE FOR ADMIN) */}
                    {isAdmin && (
                        <div className="bg-blue-50 p-5 rounded-xl border border-blue-100 shadow-sm">
                            <h3 className="text-xs font-bold text-blue-800 uppercase mb-3 flex items-center gap-2">
                                🛡️ Área da Coordenação
                            </h3>
                            
                            <div className="mb-4">
                                <label className="text-xs font-bold text-gray-600 mb-1 block">Parecer / Observação</label>
                                <textarea 
                                    className="w-full text-sm p-2 border border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-400 outline-none"
                                    rows={3}
                                    placeholder="Escreva o motivo do deferimento/indeferimento..."
                                    value={observation}
                                    onChange={(e) => setObservation(e.target.value)}
                                ></textarea>
                            </div>

                            <div className="space-y-2">
                                <button 
                                    onClick={() => handleUpdateStatus('analyzing')}
                                    disabled={updating}
                                    className="w-full py-2 bg-blue-600 text-white text-sm font-bold rounded-lg hover:bg-blue-700 transition"
                                >
                                    🔍 Marcar em Análise
                                </button>
                                <div className="grid grid-cols-2 gap-2">
                                    <button 
                                        onClick={() => handleUpdateStatus('rejected')}
                                        disabled={updating}
                                        className="py-2 bg-red-100 text-red-700 text-sm font-bold rounded-lg hover:bg-red-200 transition"
                                    >
                                        ❌ Indeferir
                                    </button>
                                    <button 
                                        onClick={() => handleUpdateStatus('completed')}
                                        disabled={updating}
                                        className="py-2 bg-green-600 text-white text-sm font-bold rounded-lg hover:bg-green-700 transition"
                                    >
                                        ✅ Deferir
                                    </button>
                                </div>
                            </div>
                        </div>
                    )}

                </div>
            </div>
        </div>
    </div>
  );
}