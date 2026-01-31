"use client";

import { useEffect, useState } from "react";
import { useParams, useRouter } from "next/navigation";

const API_BASE = "http://127.0.0.1:8000/api";

export default function RequestDetailsPage() {
  const params = useParams();
  const id = Array.isArray(params.id) ? params.id[0] : params.id;

  const router = useRouter();
  const [request, setRequest] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [updating, setUpdating] = useState(false);
  const [observation, setObservation] = useState("");
  const [currentUser, setCurrentUser] = useState<any>(null);

  useEffect(() => {
    const fetchData = async () => {
      const token = localStorage.getItem("jwt_token");
      if (!token) {
        router.push("/login");
        return;
      }

      try {
        const meRes = await fetch(`${API_BASE}/me`, {
          headers: { Authorization: `Bearer ${token}` },
        });

        if (meRes.ok) {
          setCurrentUser(await meRes.json());
        }

        const res = await fetch(`${API_BASE}/requests/${id}`, {
          headers: { Authorization: `Bearer ${token}` },
        });

        if (res.ok) {
          const data = await res.json();
          setRequest(data);
          setObservation(data.observation || "");
        } else {
          alert("Requerimento não encontrado ou acesso negado.");
          router.back();
        }
      } catch (error) {
        console.error(error);
        alert("Erro ao carregar os dados.");
      } finally {
        setLoading(false);
      }
    };

    if (id) fetchData();
  }, [id, router]);

  const handleUpdateStatus = async (newStatus: string) => {
    if (!confirm(`Tem certeza que deseja mudar o status para: ${newStatus}?`)) {
      return;
    }

    setUpdating(true);
    const token = localStorage.getItem("jwt_token");

    try {
      const res = await fetch(`${API_BASE}/requests/${id}`, {
        method: "PUT",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify({ status: newStatus, observation }),
      });

      if (res.ok) {
        setRequest((prev: any) => ({
          ...prev,
          status: newStatus,
          observation,
        }));
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

  if (loading || !request) {
    return <div className="p-10 text-center">Carregando detalhes...</div>;
  }

  const isAdmin = ["admin", "staff", "cradt"].includes(currentUser?.role);

  const statusColors: any = {
    pending: "bg-red-100 text-red-700 border-red-200",
    analyzing: "bg-yellow-100 text-yellow-700 border-yellow-200",
    completed: "bg-green-100 text-green-700 border-green-200",
    rejected: "bg-gray-200 text-gray-800 border-gray-300",
  };

  const statusLabels: any = {
    pending: "Pendente",
    analyzing: "Em Análise",
    completed: "Deferido",
    rejected: "Indeferido",
  };

  return (
    <div className="min-h-screen bg-gray-50 py-10 px-4">
      <div className="max-w-4xl mx-auto bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
        {/* Header */}
        <div className="bg-[#0B0D3A] p-6 text-white flex justify-between items-center">
          <button
            onClick={() => router.back()}
            className="p-2 hover:bg-[#1A73E8] rounded-full transition flex items-center justify-center"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              className="h-5 w-5 text-white"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M15 19l-7-7 7-7"
              />
            </svg>
          </button>

          <h1 className="text-xl font-bold">{request.type?.name}</h1>

          <span
            className={`px-3 py-1 rounded-full text-xs font-bold border ${statusColors[request.status]}`}
          >
            {statusLabels[request.status] || request.status}
          </span>
        </div>

        <div className="p-8 grid grid-cols-1 md:grid-cols-3 gap-8">
          {/* Coluna Principal */}
          <div className="md:col-span-2 space-y-6">
            <div>
              <h3 className="text-sm font-bold text-gray-500 uppercase mb-1">
                Descrição
              </h3>
              <div className="bg-gray-50 p-4 rounded-xl text-gray-700 leading-relaxed border border-gray-100">
                {request.description || "Sem descrição fornecida."}
              </div>
            </div>

            {/* Anexos */}
            <div>
              <h3 className="text-sm font-bold text-gray-500 uppercase mb-3">
                Documentos Anexados
              </h3>

              {request.documents?.length > 0 ? (
                <div className="grid grid-cols-1 gap-3">
                  {request.documents.map((doc: any) => (
                    <a
                      key={doc.id}
                      href={`http://127.0.0.1:8000/storage/${doc.path}`}
                      target="_blank"
                      className="flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:bg-[#E8F0FE] hover:border-[#1A73E8] transition"
                    >
                      <div className="bg-[#E8F0FE] p-2 rounded text-[#1A73E8]">
                        📄
                      </div>
                      <div className="flex-1 min-w-0">
                        <p className="text-sm font-medium text-gray-700 truncate">
                          {doc.name}
                        </p>
                        <p className="text-xs text-gray-400">
                          Clique para visualizar
                        </p>
                      </div>
                    </a>
                  ))}
                </div>
              ) : (
                <p className="text-sm text-gray-400 italic">
                  Nenhum anexo enviado.
                </p>
              )}
            </div>
          </div>

          {/* Coluna Lateral */}
          <div className="space-y-6">
            {/* Dados do Aluno */}
            <div className="bg-gray-50 p-5 rounded-xl border border-gray-200">
              <h3 className="text-xs font-bold text-gray-500 uppercase mb-3">
                Dados do Solicitante
              </h3>
              <p className="text-sm font-bold text-gray-800">
                {request.user?.name}
              </p>
              <p className="text-xs text-gray-500">
                {request.user?.email}
              </p>

              <div className="text-xs text-gray-600 space-y-1 border-t border-gray-200 pt-3 mt-3">
                <p>
                  <strong>Matrícula:</strong>{" "}
                  {request.user?.matricula || "N/A"}
                </p>
                <p>
                  <strong>Curso:</strong>{" "}
                  {request.user?.course.name || "N/A"}
                </p>
                <p>
                  <strong>Telefone:</strong>{" "}
                  {request.user?.phone || "N/A"}
                </p>
              </div>
            </div>

           {/* Área da Coordenação */}
{isAdmin && (
  <div className="bg-[#F9FAFB] p-5 rounded-xl border border-gray-200 shadow-sm">
    <h3 className="text-xs font-bold text-[#1A73E8] uppercase mb-3">
      Área da Coordenação
    </h3>

    <textarea
      className="w-full text-sm p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#1A73E8] outline-none mb-4"
      rows={3}
      placeholder="Escreva o motivo do deferimento/indeferimento..."
      value={observation}
      onChange={(e) => setObservation(e.target.value)}
    />

    <div className="space-y-3">
      {/* Botão Marcar em Análise */}
      <button
        onClick={() => handleUpdateStatus("analyzing")}
        disabled={updating}
        className="w-full flex items-center justify-center gap-2 py-2 bg-yellow-50 text-yellow-700 text-sm font-semibold rounded-lg hover:bg-yellow-100 transition"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          className="h-4 w-4"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M12 8v4l3 3"
          />
        </svg>
        Marcar em Análise
      </button>

      {/* Botões Indeferir e Deferir */}
      <div className="grid grid-cols-2 gap-2">
        <button
          onClick={() => handleUpdateStatus("rejected")}
          disabled={updating}
          className="flex items-center justify-center gap-2 py-2 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200 transition"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            className="h-4 w-4"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
          Indeferir
        </button>

        <button
          onClick={() => handleUpdateStatus("completed")}
          disabled={updating}
          className="flex items-center justify-center gap-2 py-2 bg-green-50 text-green-700 text-sm font-semibold rounded-lg hover:bg-green-100 transition"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            className="h-4 w-4"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M5 13l4 4L19 7"
            />
          </svg>
          Deferir
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
