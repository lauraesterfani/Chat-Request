"use client";

import { useState, useEffect, useCallback } from "react";
import axios from "axios";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { ShieldCheck, ArrowLeft, RefreshCw, Loader2, AlertCircle } from "lucide-react";

const API_BASE = "/api";

interface AuditRecord {
  id: number;
  action: string;
  subject_type: string;
  subject_id: number | null;
  created_at: string;
  metadata: Record<string, unknown> | null;
  user?: { name: string; email: string };
}

interface ApiResponse {
  data: AuditRecord[];
  meta?: { current_page: number; last_page: number; total: number };
}

export default function AuditPage() {
  const router = useRouter();
  const [loading, setLoading] = useState(true);
  const [records, setRecords] = useState<AuditRecord[]>([]);
  const [from, setFrom] = useState("");
  const [to, setTo] = useState("");
  const [error, setError] = useState("");
  const [total, setTotal] = useState(0);

  const fetchRecords = useCallback(async () => {
    setLoading(true);
    setError("");
    try {
      const token = sessionStorage.getItem("jwt_token");
      if (!token) { router.push("/login"); return; }

      const params: Record<string, string> = {};
      if (from) params.from = from;
      if (to) params.to = to;

      const res = await axios.get<ApiResponse | AuditRecord[]>(`${API_BASE}/audit-records`, {
        headers: { Authorization: `Bearer ${token}` },
        params,
      });

      const responseData = res.data;
      if (Array.isArray(responseData)) {
        setRecords(responseData);
        setTotal(responseData.length);
      } else {
        setRecords(responseData.data ?? []);
        setTotal(responseData.meta?.total ?? (responseData.data?.length ?? 0));
      }
    } catch (err: any) {
      if (err.response?.status === 401) {
        sessionStorage.removeItem("jwt_token");
        router.push("/login");
      } else {
        setError("Erro ao carregar registros de auditoria.");
      }
    } finally {
      setLoading(false);
    }
  }, [from, to, router]);

  useEffect(() => {
    fetchRecords();
  }, [fetchRecords]);

  const formatDate = (dateStr: string) => {
    const d = new Date(dateStr);
    return d.toLocaleString("pt-BR");
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
          <div className="flex items-center gap-3">
            <span className="p-2 bg-[#004d40] text-white rounded-xl">
              <ShieldCheck size={22} />
            </span>
            <div>
              <h1 className="text-2xl font-bold text-gray-900">Auditoria</h1>
              <p className="text-sm text-gray-500">Registros de ações do sistema</p>
            </div>
          </div>
        </div>

        {/* Filters */}
        <div className="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 mb-6">
          <h2 className="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">
            Filtros de Período
          </h2>
          <div className="flex flex-col sm:flex-row gap-4 items-end">
            <div className="flex-1">
              <label className="block text-sm font-medium text-gray-700 mb-1">De</label>
              <input
                type="date"
                value={from}
                onChange={(e) => setFrom(e.target.value)}
                className="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#004d40] focus:border-transparent"
              />
            </div>
            <div className="flex-1">
              <label className="block text-sm font-medium text-gray-700 mb-1">Até</label>
              <input
                type="date"
                value={to}
                onChange={(e) => setTo(e.target.value)}
                className="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#004d40] focus:border-transparent"
              />
            </div>
            <button
              onClick={fetchRecords}
              disabled={loading}
              className="flex items-center gap-2 px-5 py-2 bg-[#004d40] text-white rounded-xl text-sm font-semibold hover:bg-[#00695c] transition-colors disabled:opacity-60"
            >
              {loading ? <Loader2 size={16} className="animate-spin" /> : <RefreshCw size={16} />}
              Aplicar
            </button>
            {(from || to) && (
              <button
                onClick={() => { setFrom(""); setTo(""); }}
                className="px-4 py-2 border border-gray-200 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-50 transition-colors"
              >
                Limpar
              </button>
            )}
          </div>
        </div>

        {/* Error */}
        {error && (
          <div className="flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 rounded-xl p-4 mb-6">
            <AlertCircle size={18} />
            <span className="text-sm">{error}</span>
          </div>
        )}

        {/* Table */}
        <div className="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
          <div className="p-5 border-b border-gray-100 flex items-center justify-between">
            <h2 className="font-semibold text-gray-800">Registros</h2>
            {!loading && (
              <span className="text-xs text-gray-500 bg-gray-100 px-3 py-1 rounded-full">
                {total} {total === 1 ? "registro" : "registros"}
              </span>
            )}
          </div>

          {loading ? (
            <div className="flex justify-center items-center py-20">
              <Loader2 size={32} className="animate-spin text-[#004d40]" />
            </div>
          ) : records.length === 0 ? (
            <div className="flex flex-col items-center justify-center py-20 text-gray-400">
              <ShieldCheck size={48} className="mb-3 opacity-30" />
              <p className="text-sm">Nenhum registro encontrado</p>
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                      Ação
                    </th>
                    <th className="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                      Tipo do Sujeito
                    </th>
                    <th className="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">
                      Usuário
                    </th>
                    <th className="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                      Data
                    </th>
                    <th className="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">
                      Metadata
                    </th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-50">
                  {records.map((record) => (
                    <tr key={record.id} className="hover:bg-gray-50 transition-colors">
                      <td className="px-5 py-4">
                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-medium bg-[#004d40]/10 text-[#004d40]">
                          {record.action}
                        </span>
                      </td>
                      <td className="px-5 py-4 text-gray-700 font-medium">
                        {record.subject_type}
                        {record.subject_id != null && (
                          <span className="text-gray-400 text-xs ml-1">#{record.subject_id}</span>
                        )}
                      </td>
                      <td className="px-5 py-4 text-gray-600 hidden md:table-cell">
                        {record.user ? (
                          <div>
                            <p className="font-medium text-gray-800">{record.user.name}</p>
                            <p className="text-xs text-gray-400">{record.user.email}</p>
                          </div>
                        ) : (
                          <span className="text-gray-400 text-xs">—</span>
                        )}
                      </td>
                      <td className="px-5 py-4 text-gray-600 whitespace-nowrap">
                        {formatDate(record.created_at)}
                      </td>
                      <td className="px-5 py-4 hidden lg:table-cell">
                        {record.metadata ? (
                          <pre className="text-xs bg-gray-50 rounded-lg p-2 max-w-xs overflow-auto max-h-20 text-gray-600 border border-gray-100">
                            {JSON.stringify(record.metadata, null, 2)}
                          </pre>
                        ) : (
                          <span className="text-gray-400 text-xs">—</span>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
