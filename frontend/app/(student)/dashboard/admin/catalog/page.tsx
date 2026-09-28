"use client";

import { useState, useEffect, useCallback } from "react";
import axios from "axios";
import Link from "next/link";
import { useRouter } from "next/navigation";
import {
  BookOpen,
  ArrowLeft,
  Plus,
  Loader2,
  AlertCircle,
  X,
  CheckSquare,
  Square,
  Wifi,
  WifiOff,
} from "lucide-react";

const API_BASE = "/api";

interface ServiceCatalogEntry {
  id: number;
  title: string;
  description: string | null;
  is_digital: boolean;
  external_channel_hint: string | null;
  type_request?: { id: number; name: string };
}

interface TypeRequest {
  id: number;
  name: string;
}

interface NewServiceForm {
  type_request_id: string;
  title: string;
  description: string;
  is_digital: boolean;
  external_channel_hint: string;
}

const emptyForm: NewServiceForm = {
  type_request_id: "",
  title: "",
  description: "",
  is_digital: false,
  external_channel_hint: "",
};

export default function CatalogPage() {
  const router = useRouter();
  const [loading, setLoading] = useState(true);
  const [catalog, setCatalog] = useState<ServiceCatalogEntry[]>([]);
  const [typeRequests, setTypeRequests] = useState<TypeRequest[]>([]);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [form, setForm] = useState<NewServiceForm>(emptyForm);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState("");
  const [formError, setFormError] = useState("");
  const [successMsg, setSuccessMsg] = useState("");

  const getToken = useCallback(() => {
    const token = sessionStorage.getItem("jwt_token");
    if (!token) { router.push("/login"); return null; }
    return token;
  }, [router]);

  const fetchCatalog = useCallback(async () => {
    setLoading(true);
    setError("");
    try {
      const token = getToken();
      if (!token) return;

      const res = await axios.get<ServiceCatalogEntry[]>(`${API_BASE}/admin/service-catalog`, {
        headers: { Authorization: `Bearer ${token}` },
      });
      setCatalog(Array.isArray(res.data) ? res.data : (res.data as any).data ?? []);
    } catch (err: any) {
      if (err.response?.status === 401) {
        sessionStorage.removeItem("jwt_token");
        router.push("/login");
      } else {
        setError("Erro ao carregar catálogo de serviços.");
      }
    } finally {
      setLoading(false);
    }
  }, [getToken, router]);

  const fetchTypeRequests = useCallback(async () => {
    try {
      const token = getToken();
      if (!token) return;
      const res = await axios.get<TypeRequest[]>(`${API_BASE}/type-requests`, {
        headers: { Authorization: `Bearer ${token}` },
      });
      setTypeRequests(Array.isArray(res.data) ? res.data : (res.data as any).data ?? []);
    } catch {
      // non-critical
    }
  }, [getToken]);

  useEffect(() => {
    fetchCatalog();
    fetchTypeRequests();
  }, [fetchCatalog, fetchTypeRequests]);

  const openModal = () => {
    setForm(emptyForm);
    setFormError("");
    setIsModalOpen(true);
  };

  const closeModal = () => {
    setIsModalOpen(false);
    setForm(emptyForm);
    setFormError("");
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setFormError("");

    if (!form.title.trim()) {
      setFormError("O título é obrigatório.");
      return;
    }

    setSubmitting(true);
    try {
      const token = getToken();
      if (!token) return;

      await axios.post(
        `${API_BASE}/admin/service-catalog`,
        {
          type_request_id: form.type_request_id || undefined,
          title: form.title.trim(),
          description: form.description.trim() || undefined,
          is_digital: form.is_digital,
          external_channel_hint: form.external_channel_hint.trim() || undefined,
        },
        { headers: { Authorization: `Bearer ${token}` } }
      );

      setSuccessMsg("Serviço criado com sucesso!");
      setTimeout(() => setSuccessMsg(""), 4000);
      closeModal();
      fetchCatalog();
    } catch (err: any) {
      const msg = err.response?.data?.message ?? "Erro ao criar serviço.";
      setFormError(msg);
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="min-h-screen bg-gray-50 p-4 sm:p-8">
      <div className="max-w-7xl mx-auto">

        {/* Header */}
        <div className="mb-8 flex items-center gap-4">
          <Link
            href="/dashboard/admin"
            className="p-2 rounded-xl border border-gray-200 hover:bg-white hover:shadow-sm transition-all text-gray-500"
          >
            <ArrowLeft size={20} />
          </Link>
          <div className="flex items-center gap-3 flex-1">
            <span className="p-2 bg-[#004d40] text-white rounded-xl">
              <BookOpen size={22} />
            </span>
            <div>
              <h1 className="text-2xl font-bold text-gray-900">Catálogo de Serviços</h1>
              <p className="text-sm text-gray-500">Gerencie os serviços disponíveis para solicitação</p>
            </div>
          </div>
          <button
            onClick={openModal}
            className="flex items-center gap-2 px-4 py-2 bg-[#004d40] text-white rounded-xl text-sm font-semibold hover:bg-[#00695c] transition-colors"
          >
            <Plus size={18} />
            Novo serviço
          </button>
        </div>

        {/* Success message */}
        {successMsg && (
          <div className="flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl p-4 mb-6">
            <CheckSquare size={18} />
            <span className="text-sm font-medium">{successMsg}</span>
          </div>
        )}

        {/* Error */}
        {error && (
          <div className="flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 rounded-xl p-4 mb-6">
            <AlertCircle size={18} />
            <span className="text-sm">{error}</span>
          </div>
        )}

        {/* Content */}
        {loading ? (
          <div className="flex justify-center items-center py-20">
            <Loader2 size={32} className="animate-spin text-[#004d40]" />
          </div>
        ) : catalog.length === 0 ? (
          <div className="flex flex-col items-center justify-center py-20 text-gray-400 bg-white rounded-2xl border border-gray-100 shadow-sm">
            <BookOpen size={48} className="mb-3 opacity-30" />
            <p className="text-sm font-medium">Nenhum serviço cadastrado</p>
            <button
              onClick={openModal}
              className="mt-4 flex items-center gap-2 px-4 py-2 bg-[#004d40] text-white rounded-xl text-sm font-semibold hover:bg-[#00695c] transition-colors"
            >
              <Plus size={16} />
              Criar primeiro serviço
            </button>
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            {catalog.map((entry) => (
              <div
                key={entry.id}
                className="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col gap-3 hover:border-[#004d40]/30 hover:shadow-md transition-all"
              >
                <div className="flex items-start justify-between gap-3">
                  <h3 className="font-bold text-gray-900 text-base leading-tight">{entry.title}</h3>
                  <span
                    className={`shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-xs font-medium ${
                      entry.is_digital
                        ? "bg-blue-50 text-blue-600"
                        : "bg-gray-100 text-gray-500"
                    }`}
                  >
                    {entry.is_digital ? <Wifi size={12} /> : <WifiOff size={12} />}
                    {entry.is_digital ? "Digital" : "Presencial"}
                  </span>
                </div>

                {entry.description && (
                  <p className="text-sm text-gray-500 leading-relaxed line-clamp-3">
                    {entry.description}
                  </p>
                )}

                <div className="mt-auto pt-3 border-t border-gray-50 space-y-1.5">
                  {entry.type_request && (
                    <p className="text-xs text-gray-400">
                      <span className="font-medium text-gray-600">Tipo: </span>
                      {entry.type_request.name}
                    </p>
                  )}
                  {entry.external_channel_hint && (
                    <p className="text-xs text-gray-400 truncate">
                      <span className="font-medium text-gray-600">Canal externo: </span>
                      {entry.external_channel_hint}
                    </p>
                  )}
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Modal */}
      {isModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div
            className="absolute inset-0 bg-black/40 backdrop-blur-sm"
            onClick={closeModal}
          />
          <div className="relative bg-white w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden">
            {/* Modal Header */}
            <div className="p-6 border-b border-gray-100 flex items-center justify-between">
              <h2 className="text-xl font-bold text-gray-900 flex items-center gap-2">
                <BookOpen size={20} className="text-[#004d40]" />
                Novo Serviço
              </h2>
              <button
                onClick={closeModal}
                className="p-2 hover:bg-gray-100 rounded-full transition-colors text-gray-400"
              >
                <X size={20} />
              </button>
            </div>

            {/* Modal Body */}
            <form onSubmit={handleSubmit} className="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
              {formError && (
                <div className="flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 rounded-xl p-3">
                  <AlertCircle size={16} />
                  <span className="text-sm">{formError}</span>
                </div>
              )}

              {/* Tipo de Requerimento */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Tipo de Requerimento
                </label>
                <select
                  value={form.type_request_id}
                  onChange={(e) => setForm({ ...form, type_request_id: e.target.value })}
                  className="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#004d40] focus:border-transparent bg-white"
                >
                  <option value="">Selecionar tipo (opcional)</option>
                  {typeRequests.map((t) => (
                    <option key={t.id} value={String(t.id)}>
                      {t.name}
                    </option>
                  ))}
                </select>
              </div>

              {/* Título */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Título <span className="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  value={form.title}
                  onChange={(e) => setForm({ ...form, title: e.target.value })}
                  placeholder="Ex: Declaração de Matrícula"
                  className="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#004d40] focus:border-transparent"
                />
              </div>

              {/* Descrição */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Descrição
                </label>
                <textarea
                  value={form.description}
                  onChange={(e) => setForm({ ...form, description: e.target.value })}
                  rows={3}
                  placeholder="Descreva o serviço..."
                  className="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#004d40] focus:border-transparent resize-none"
                />
              </div>

              {/* Canal externo */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Canal externo (hint)
                </label>
                <input
                  type="text"
                  value={form.external_channel_hint}
                  onChange={(e) => setForm({ ...form, external_channel_hint: e.target.value })}
                  placeholder="Ex: https://sei.ufpa.br ou presencial na CRADT"
                  className="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#004d40] focus:border-transparent"
                />
              </div>

              {/* is_digital */}
              <div>
                <label className="flex items-center gap-3 cursor-pointer group">
                  <button
                    type="button"
                    onClick={() => setForm({ ...form, is_digital: !form.is_digital })}
                    className="text-[#004d40] transition-colors"
                  >
                    {form.is_digital ? <CheckSquare size={22} /> : <Square size={22} className="text-gray-400" />}
                  </button>
                  <span className="text-sm font-medium text-gray-700">Serviço digital</span>
                  <span className="text-xs text-gray-400">(atendimento online)</span>
                </label>
              </div>
            </form>

            {/* Modal Footer */}
            <div className="p-5 border-t border-gray-100 flex gap-3 justify-end">
              <button
                type="button"
                onClick={closeModal}
                disabled={submitting}
                className="px-5 py-2.5 border border-gray-200 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-50 transition-colors disabled:opacity-60"
              >
                Cancelar
              </button>
              <button
                onClick={handleSubmit}
                disabled={submitting}
                className="flex items-center gap-2 px-5 py-2.5 bg-[#004d40] text-white rounded-xl text-sm font-semibold hover:bg-[#00695c] transition-colors disabled:opacity-60"
              >
                {submitting ? <Loader2 size={16} className="animate-spin" /> : <Plus size={16} />}
                Criar serviço
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
