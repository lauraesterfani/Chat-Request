"use client";

import { useEffect, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import { X, FileText, Check, Ban, Clock, ChevronLeft, ExternalLink } from "lucide-react";

const API_BASE = "http://127.0.0.1:8000/api";

// --- Subcomponente: Modal de Confirmação ---
function ModalConfirm({ status, onConfirm, onCancel, statusLabels }: any) {
  return (
    <div className="fixed inset-0 z-[100] bg-black/40 backdrop-blur-sm flex items-center justify-center p-6">
      <div className="bg-white rounded-xl shadow-2xl max-w-sm w-full p-6 relative animate-in fade-in zoom-in duration-200">
        <button onClick={onCancel} className="absolute top-4 right-4 text-slate-400 hover:text-black transition">
          <X size={20} />
        </button>
        <h2 className="text-lg font-bold text-slate-800 mb-4">Confirmar alteração</h2>
        <p className="text-sm text-slate-600 mb-6">
          Tem certeza que deseja mudar o status para:{" "}
          <span className="font-semibold text-[#1A73E8]">{statusLabels[status] || status}</span>?
        </p>
        <div className="flex justify-end gap-3">
          <button onClick={onCancel} className="px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-lg">
            Cancelar
          </button>
          <button onClick={onConfirm} className="px-4 py-2 text-sm font-bold bg-[#1A73E8] text-white rounded-lg hover:bg-[#1662c4] transition">
            Confirmar
          </button>
        </div>
      </div>
    </div>
  );
}

// --- Componente Principal ---
export default function RequestDetailsPage() {
  const params = useParams();
  const router = useRouter();
  const id = Array.isArray(params.id) ? params.id[0] : params.id;

  // Estados de Dados
  const [request, setRequest] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [updating, setUpdating] = useState(false);
  const [observation, setObservation] = useState("");
  const [currentUser, setCurrentUser] = useState<any>(null);
  
  // Estados de UI (Modais e Alertas)
  const [showConfirm, setShowConfirm] = useState(false);
  const [nextStatus, setNextStatus] = useState("");
  const [feedback, setFeedback] = useState<{ msg: string; type: 'success' | 'error' } | null>(null);

  const statusColors: any = {
    pending: "bg-red-100 text-red-700 border-red-200",
    analyzing: "bg-yellow-100 text-yellow-700 border-yellow-200",
    completed: "bg-green-100 text-green-700 border-green-200",
    canceled: "bg-gray-200 text-gray-800 border-gray-300",
  };

  const statusLabels: any = {
    pending: "Pendente",
    analyzing: "Em Análise",
    completed: "Deferido",
    canceled: "Indeferido",
  };

  // Carregamento Inicial
  useEffect(() => {
    const fetchData = async () => {
      const token = localStorage.getItem("jwt_token");
      if (!token) return router.push("/login");

      try {
        const [meRes, reqRes] = await Promise.all([
          fetch(`${API_BASE}/me`, { headers: { Authorization: `Bearer ${token}` } }),
          fetch(`${API_BASE}/requests/${id}`, { headers: { Authorization: `Bearer ${token}` } })
        ]);

        if (meRes.ok) setCurrentUser(await meRes.json());
        
        if (reqRes.ok) {
          const data = await reqRes.json();
          setRequest(data);
          setObservation(data.observation || "");
        } else {
          router.back();
        }
      } catch (error) {
        console.error("Erro ao carregar dados:", error);
      } finally {
        setLoading(false);
      }
    };

    if (id) fetchData();
  }, [id, router]);

  // Lógica de Feedback
  const handleFeedback = (msg: string, type: 'success' | 'error') => {
    setFeedback({ msg, type });
    setTimeout(() => setFeedback(null), 4000);
  };

  // Lógica de Alteração de Status
  const handleStatusClick = (status: string) => {
    setNextStatus(status);
    setShowConfirm(true);
  };

  const confirmStatusChange = async () => {
    setShowConfirm(false);
    setUpdating(true);
    
    try {
      const token = localStorage.getItem("jwt_token");
      const res = await fetch(`${API_BASE}/requests/${id}`, {
        method: "PUT",
        headers: { 
          "Authorization": `Bearer ${token}`,
          "Content-Type": "application/json"
        },
        body: JSON.stringify({ status: nextStatus, observation }),
      });

      if (res.ok) {
        setRequest((prev: any) => ({ ...prev, status: nextStatus, observation }));
        handleFeedback("Status atualizado com sucesso!", "success");
      } else {
        handleFeedback("Erro ao atualizar status no servidor.", "error");
      }
    } catch (error) {
      handleFeedback("Erro de conexão com a API.", "error");
    } finally {
      setUpdating(false);
    }
  };

  if (loading || !request) {
    return (
      <div className="flex flex-col items-center justify-center min-h-screen gap-4">
        <div className="w-10 h-10 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin" />
        <p className="text-slate-500 animate-pulse">Carregando detalhes...</p>
      </div>
    );
  }

  const isAdmin = ["admin", "staff", "cradt"].includes(currentUser?.role);

  return (
    <div className="min-h-screen bg-gray-50 py-10 px-4">
      {/* Toast Feedback */}
      {feedback && (
        <div className="fixed top-6 left-1/2 -translate-x-1/2 z-[110] animate-in slide-in-from-top-4 duration-300">
          <div className={`px-6 py-3 rounded-full shadow-2xl flex items-center gap-2 text-white font-bold text-sm ${
            feedback.type === 'success' ? "bg-green-600" : "bg-red-600"
          }`}>
            {feedback.type === 'success' ? <Check size={18} /> : <X size={18} />}
            {feedback.msg}
          </div>
        </div>
      )}

      <div className="max-w-4xl mx-auto bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
        {/* Header */}
        <div className="bg-[#0B0D3A] p-6 text-white flex justify-between items-center">
          <button onClick={() => router.back()} className="p-2 hover:bg-white/10 rounded-full transition">
            <ChevronLeft size={24} />
          </button>
          <h1 className="text-xl font-bold truncate px-4">{request.type?.name}</h1>
          <span className={`px-4 py-1 rounded-full text-xs font-black border uppercase ${statusColors[request.status]}`}>
            {statusLabels[request.status] || request.status}
          </span>
        </div>

        <div className="p-8 grid grid-cols-1 md:grid-cols-3 gap-8">
          {/* Coluna Principal: Conteúdo */}
          <div className="md:col-span-2 space-y-8">
            <section>
              <h3 className="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Descrição do Requerimento</h3>
              <div className="bg-gray-50 p-5 rounded-xl text-gray-700 border border-gray-100 leading-relaxed shadow-sm">
                {request.description || "Nenhuma descrição fornecida."}
              </div>
            </section>

            <section>
              <h3 className="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Documentos Anexados</h3>
              {request.documents?.length > 0 ? (
                <div className="grid grid-cols-1 gap-4">
                  {request.documents.map((doc: any) => {
                    const fileUrl = `http://127.0.0.1:8000/storage/${doc.path.replace("public/", "")}`;
                    const isImage = /\.(jpg|jpeg|png|webp|gif)$/i.test(doc.path);
                    return (
                      <div key={doc.id} className="group flex flex-col gap-3 p-4 border border-gray-200 rounded-xl hover:border-blue-400 hover:bg-blue-50/30 transition-all shadow-sm bg-white">
                        {isImage ? (
                          <img src={fileUrl} alt={doc.name} className="max-w-full rounded-lg border border-slate-200 shadow-sm object-contain max-h-80 bg-slate-100" />
                        ) : (
                          <div className="flex items-center gap-3 p-4 bg-slate-50 rounded-lg border-2 border-dashed border-slate-200">
                            <FileText size={32} className="text-slate-400" />
                            <span className="text-sm font-medium text-slate-500">Documento PDF ou Arquivo</span>
                          </div>
                        )}
                        <div className="flex justify-between items-center border-t pt-3 mt-1">
                          <p className="text-sm font-semibold text-gray-700 truncate max-w-[200px]">{doc.name}</p>
                          <a href={fileUrl} target="_blank" rel="noopener noreferrer" className="flex items-center gap-1 text-xs font-bold text-[#1A73E8] hover:underline">
                            ABRIR EM NOVA ABA <ExternalLink size={14} />
                          </a>
                        </div>
                      </div>
                    );
                  })}
                </div>
              ) : (
                <p className="text-sm text-gray-400 italic">Nenhum anexo encontrado.</p>
              )}
            </section>
          </div>

          {/* Coluna Lateral: Sidebar */}
          <div className="space-y-6">
            <div className="bg-gray-50 p-5 rounded-xl border border-gray-200">
              <h3 className="text-[10px] font-black text-gray-400 uppercase mb-4 tracking-widest">Solicitante</h3>
              <div className="space-y-1">
                <p className="text-sm font-bold text-gray-800">{request.user?.name}</p>
                <p className="text-xs text-gray-500 truncate mb-4">{request.user?.email}</p>
              </div>
              <div className="text-xs text-gray-600 space-y-3 border-t border-gray-200 pt-4 mt-2">
                <p><strong>Matrícula:</strong> {request.user?.matricula || "---"}</p>
                <p><strong>Curso:</strong> {request.user?.course?.name || "---"}</p>
              </div>
            </div>

            {isAdmin && (
              <div className="bg-white p-5 rounded-xl border-2 border-blue-100 shadow-md">
                <h3 className="text-[10px] font-black text-blue-600 uppercase mb-4 tracking-widest text-center">Área da Coordenação</h3>
                <textarea
                  className="w-full text-sm p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none mb-4 min-h-[120px] transition-all"
                  placeholder="Justifique o deferimento ou indeferimento..."
                  value={observation}
                  onChange={(e) => setObservation(e.target.value)}
                />
                <div className="space-y-2">
                  <button onClick={() => handleStatusClick("analyzing")} disabled={updating}
                    className="w-full flex items-center justify-center gap-2 py-2.5 bg-yellow-50 text-yellow-700 text-xs font-bold rounded-lg hover:bg-yellow-100 transition disabled:opacity-50">
                    <Clock size={16} /> EM ANÁLISE
                  </button>
                  <div className="grid grid-cols-2 gap-2">
                    <button onClick={() => handleStatusClick("canceled")} disabled={updating}
                      className="flex items-center justify-center gap-1 py-2.5 bg-slate-100 text-slate-700 text-xs font-bold rounded-lg hover:bg-slate-200 transition">
                      <Ban size={16} /> INDEFERIR
                    </button>
                    <button onClick={() => handleStatusClick("completed")} disabled={updating}
                      className="flex items-center justify-center gap-1 py-2.5 bg-green-600 text-white text-xs font-bold rounded-lg hover:bg-green-700 shadow-md transition">
                      <Check size={16} /> DEFERIR
                    </button>
                  </div>
                </div>
              </div>
            )}
          </div>
        </div>
      </div>

      {showConfirm && (
        <ModalConfirm
          status={nextStatus}
          statusLabels={statusLabels}
          onConfirm={confirmStatusChange}
          onCancel={() => setShowConfirm(false)}
        />
      )}
    </div>
  );
}