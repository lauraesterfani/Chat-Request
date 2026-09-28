"use client";

import { useState, useEffect, useCallback } from "react";
import axios from "axios";
import Link from "next/link";
import { useRouter } from "next/navigation";
import {
  BarChart3,
  ArrowLeft,
  RefreshCw,
  Loader2,
  AlertCircle,
  CheckCircle2,
  XCircle,
  FileText,
  Star,
} from "lucide-react";

const API_BASE = "/api";

interface MetricsData {
  total: number;
  open: number;
  completed: number;
  cancelled: number;
  satisfaction?: {
    total_responses: number;
    average_rating: number | null;
  };
}

interface Course {
  id: number;
  name: string;
}

export default function MetricsPage() {
  const router = useRouter();
  const [loading, setLoading] = useState(true);
  const [metrics, setMetrics] = useState<MetricsData | null>(null);
  const [courses, setCourses] = useState<Course[]>([]);
  const [from, setFrom] = useState("");
  const [to, setTo] = useState("");
  const [courseId, setCourseId] = useState("");
  const [error, setError] = useState("");

  useEffect(() => {
    const fetchCourses = async () => {
      try {
        const res = await axios.get<Course[]>(`${API_BASE}/courses`);
        setCourses(res.data);
      } catch {
        // courses are optional for filters, ignore errors
      }
    };
    fetchCourses();
  }, []);

  const fetchMetrics = useCallback(async () => {
    setLoading(true);
    setError("");
    try {
      const token = sessionStorage.getItem("jwt_token");
      if (!token) { router.push("/login"); return; }

      const params: Record<string, string> = {};
      if (from) params.from = from;
      if (to) params.to = to;
      if (courseId) params.course_id = courseId;

      const res = await axios.get<MetricsData>(`${API_BASE}/metrics`, {
        headers: { Authorization: `Bearer ${token}` },
        params,
      });
      setMetrics(res.data);
    } catch (err: any) {
      if (err.response?.status === 401) {
        sessionStorage.removeItem("jwt_token");
        router.push("/login");
      } else {
        setError("Erro ao carregar métricas.");
      }
    } finally {
      setLoading(false);
    }
  }, [from, to, courseId, router]);

  useEffect(() => {
    fetchMetrics();
  }, [fetchMetrics]);

  const statCards = metrics
    ? [
        {
          label: "Total de Requerimentos",
          value: metrics.total,
          icon: FileText,
          bg: "bg-[#004d40]/10",
          color: "text-[#004d40]",
        },
        {
          label: "Abertos",
          value: metrics.open,
          icon: RefreshCw,
          bg: "bg-blue-50",
          color: "text-blue-600",
        },
        {
          label: "Concluídos",
          value: metrics.completed,
          icon: CheckCircle2,
          bg: "bg-emerald-50",
          color: "text-emerald-600",
        },
        {
          label: "Cancelados",
          value: metrics.cancelled,
          icon: XCircle,
          bg: "bg-red-50",
          color: "text-red-500",
        },
      ]
    : [];

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
              <BarChart3 size={22} />
            </span>
            <div>
              <h1 className="text-2xl font-bold text-gray-900">Métricas</h1>
              <p className="text-sm text-gray-500">Visão quantitativa dos requerimentos</p>
            </div>
          </div>
        </div>

        {/* Filters */}
        <div className="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 mb-6">
          <h2 className="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">
            Filtros
          </h2>
          <div className="flex flex-col sm:flex-row gap-4 items-end flex-wrap">
            <div className="flex-1 min-w-[140px]">
              <label className="block text-sm font-medium text-gray-700 mb-1">De</label>
              <input
                type="date"
                value={from}
                onChange={(e) => setFrom(e.target.value)}
                className="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#004d40] focus:border-transparent"
              />
            </div>
            <div className="flex-1 min-w-[140px]">
              <label className="block text-sm font-medium text-gray-700 mb-1">Até</label>
              <input
                type="date"
                value={to}
                onChange={(e) => setTo(e.target.value)}
                className="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#004d40] focus:border-transparent"
              />
            </div>
            <div className="flex-1 min-w-[160px]">
              <label className="block text-sm font-medium text-gray-700 mb-1">Curso</label>
              <select
                value={courseId}
                onChange={(e) => setCourseId(e.target.value)}
                className="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#004d40] focus:border-transparent bg-white"
              >
                <option value="">Todos os cursos</option>
                {courses.map((c) => (
                  <option key={c.id} value={String(c.id)}>
                    {c.name}
                  </option>
                ))}
              </select>
            </div>
            <button
              onClick={fetchMetrics}
              disabled={loading}
              className="flex items-center gap-2 px-5 py-2 bg-[#004d40] text-white rounded-xl text-sm font-semibold hover:bg-[#00695c] transition-colors disabled:opacity-60"
            >
              {loading ? <Loader2 size={16} className="animate-spin" /> : <RefreshCw size={16} />}
              Atualizar
            </button>
            {(from || to || courseId) && (
              <button
                onClick={() => { setFrom(""); setTo(""); setCourseId(""); }}
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

        {/* Stat Cards */}
        {loading ? (
          <div className="flex justify-center items-center py-20">
            <Loader2 size={32} className="animate-spin text-[#004d40]" />
          </div>
        ) : metrics ? (
          <>
            <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
              {statCards.map((card) => {
                const Icon = card.icon;
                return (
                  <div
                    key={card.label}
                    className="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4"
                  >
                    <div className={`p-3 rounded-xl ${card.bg}`}>
                      <Icon size={22} className={card.color} />
                    </div>
                    <div>
                      <p className="text-xs text-gray-500 font-medium leading-tight">{card.label}</p>
                      <p className="text-3xl font-bold text-slate-800 mt-0.5">{card.value}</p>
                    </div>
                  </div>
                );
              })}
            </div>

            {/* Satisfaction Section */}
            {metrics.satisfaction && (
              <div className="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <div className="flex items-center gap-3 mb-5">
                  <span className="p-2 bg-amber-50 rounded-xl">
                    <Star size={20} className="text-amber-500" />
                  </span>
                  <h2 className="text-lg font-bold text-gray-800">Satisfação</h2>
                </div>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div className="bg-gray-50 rounded-xl p-4 text-center">
                    <p className="text-xs text-gray-500 uppercase tracking-wider mb-1">Total de Respostas</p>
                    <p className="text-4xl font-bold text-slate-800">
                      {metrics.satisfaction.total_responses}
                    </p>
                  </div>
                  <div className="bg-amber-50 rounded-xl p-4 text-center">
                    <p className="text-xs text-gray-500 uppercase tracking-wider mb-1">Nota Média</p>
                    <p className="text-4xl font-bold text-amber-600">
                      {metrics.satisfaction.average_rating != null
                        ? metrics.satisfaction.average_rating.toFixed(1)
                        : "—"}
                    </p>
                    {metrics.satisfaction.average_rating != null && (
                      <p className="text-xs text-gray-400 mt-1">de 5.0</p>
                    )}
                  </div>
                </div>
              </div>
            )}
          </>
        ) : null}
      </div>
    </div>
  );
}
