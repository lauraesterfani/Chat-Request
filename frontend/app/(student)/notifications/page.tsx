'use client';

import { useCallback, useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { useAuth } from '../../context/AuthContext';
import {
  Bell,
  CheckCheck,
  ChevronLeft,
  ChevronRight,
  ExternalLink,
  Loader2,
  SlidersHorizontal,
} from 'lucide-react';

// ─────────────────────────────────────────────────────────────────────────────
// Tipos
// ─────────────────────────────────────────────────────────────────────────────

interface Notification {
  id: string;
  title: string;
  body: string;
  category?: string | null;
  link?: string | null;
  read_at?: string | null;
  created_at: string;
}

interface PaginatedResponse {
  data: Notification[];
  current_page: number;
  last_page: number;
  total: number;
  per_page: number;
}

const CATEGORY_LABELS: Record<string, string> = {
  message: 'Mensagens',
  status: 'Status',
  request: 'Requerimentos',
  document: 'Documentos',
  workflow: 'Encaminhamentos',
};

// ─────────────────────────────────────────────────────────────────────────────
// Utilitários
// ─────────────────────────────────────────────────────────────────────────────

function formatDate(iso: string) {
  return new Date(iso).toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  });
}

// ─────────────────────────────────────────────────────────────────────────────
// Página
// ─────────────────────────────────────────────────────────────────────────────

export default function NotificationsPage() {
  const { token } = useAuth();
  const router = useRouter();

  // Filtros
  const [onlyUnread, setOnlyUnread] = useState(false);
  const [category, setCategory] = useState('');

  // Dados
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(true);
  const [markingAll, setMarkingAll] = useState(false);

  // ── Busca paginada ────────────────────────────────────────────────────────

  const fetchNotifications = useCallback(async (currentPage: number) => {
    if (!token) return;
    setLoading(true);
    try {
      const params = new URLSearchParams({ per_page: '20', page: String(currentPage) });
      if (onlyUnread) params.set('unread', 'true');
      if (category) params.set('category', category);

      const res = await fetch(`/api/notifications?${params.toString()}`, {
        headers: { Authorization: `Bearer ${token}` },
      });
      if (!res.ok) return;
      const json: PaginatedResponse = await res.json();
      setNotifications(json.data ?? []);
      setLastPage(json.last_page ?? 1);
      setTotal(json.total ?? 0);
    } finally {
      setLoading(false);
    }
  }, [token, onlyUnread, category]);

  useEffect(() => {
    setPage(1);
  }, [onlyUnread, category]);

  useEffect(() => {
    fetchNotifications(page);
  }, [fetchNotifications, page]);

  // ── Marcar como lida ──────────────────────────────────────────────────────

  const markRead = async (id: string) => {
    if (!token) return;
    await fetch(`/api/notifications/${id}/read`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token}` },
    });
    setNotifications((prev) =>
      prev.map((n) => (n.id === id ? { ...n, read_at: new Date().toISOString() } : n))
    );
  };

  // ── Marcar todas como lidas ───────────────────────────────────────────────

  const markAllRead = async () => {
    if (!token) return;
    setMarkingAll(true);
    try {
      await fetch('/api/notifications/read-all', {
        method: 'POST',
        headers: { Authorization: `Bearer ${token}` },
      });
      await fetchNotifications(page);
    } finally {
      setMarkingAll(false);
    }
  };

  // ─────────────────────────────────────────────────────────────────────────
  // Render
  // ─────────────────────────────────────────────────────────────────────────

  const unreadCount = notifications.filter((n) => !n.read_at).length;

  return (
    <div className="min-h-screen bg-[#F4F6F8] py-10 px-4">
      <div className="max-w-3xl mx-auto">

        {/* Cabeçalho */}
        <div className="bg-[#004d40] rounded-3xl p-6 text-white mb-6 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <button
              onClick={() => router.push('/me')}
              aria-label="Voltar"
              className="p-2 hover:bg-white/10 rounded-full transition"
            >
              <ChevronLeft size={22} />
            </button>
            <Bell size={22} />
            <h1 className="text-xl font-bold">Notificações</h1>
          </div>
          <button
            disabled={unreadCount === 0 || markingAll}
            onClick={markAllRead}
            className="flex items-center gap-1.5 text-sm font-semibold bg-white/10 hover:bg-white/20 disabled:opacity-40 disabled:cursor-not-allowed px-3 py-1.5 rounded-xl transition"
          >
            {markingAll ? <Loader2 size={14} className="animate-spin" /> : <CheckCheck size={14} />}
            Marcar todas como lidas
          </button>
        </div>

        {/* Filtros */}
        <div className="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-5 flex flex-wrap gap-3 items-center">
          <SlidersHorizontal size={16} className="text-gray-400" />

          {/* Toggle lidas/não lidas */}
          <button
            onClick={() => setOnlyUnread((v) => !v)}
            className={`px-3 py-1.5 rounded-xl text-xs font-bold border transition ${
              onlyUnread
                ? 'bg-[#004d40] text-white border-[#004d40]'
                : 'bg-white text-gray-600 border-gray-200 hover:border-emerald-400'
            }`}
          >
            {onlyUnread ? 'Apenas não lidas ✓' : 'Todas'}
          </button>

          {/* Filtro por categoria */}
          <select
            value={category}
            onChange={(e) => setCategory(e.target.value)}
            className="text-xs border border-gray-200 rounded-xl px-3 py-1.5 text-gray-600 focus:outline-none focus:ring-2 focus:ring-emerald-400 bg-white"
          >
            <option value="">Todas as categorias</option>
            {Object.entries(CATEGORY_LABELS).map(([k, v]) => (
              <option key={k} value={k}>{v}</option>
            ))}
          </select>

          {total > 0 && (
            <span className="ml-auto text-xs text-gray-400">{total} no total</span>
          )}
        </div>

        {/* Lista */}
        {loading ? (
          <div className="flex justify-center py-20">
            <Loader2 size={36} className="text-emerald-600 animate-spin" />
          </div>
        ) : notifications.length === 0 ? (
          <div className="bg-white rounded-2xl border border-gray-100 shadow-sm p-12 text-center">
            <Bell size={40} className="text-gray-200 mx-auto mb-3" />
            <p className="text-gray-400 font-medium">Nenhuma notificação.</p>
          </div>
        ) : (
          <div className="space-y-3">
            {notifications.map((item) => (
              <div
                key={item.id}
                className={`bg-white rounded-2xl border shadow-sm p-5 transition ${
                  item.read_at
                    ? 'border-gray-100'
                    : 'border-emerald-200 bg-emerald-50/30'
                }`}
              >
                <div className="flex items-start justify-between gap-4">
                  {/* Conteúdo */}
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2 mb-1">
                      {!item.read_at && (
                        <span className="w-2 h-2 rounded-full bg-[#108542] shrink-0" aria-label="Não lida" />
                      )}
                      <p className="text-sm font-bold text-gray-800 truncate">{item.title}</p>
                    </div>
                    <p className="text-xs text-gray-500 leading-relaxed">{item.body}</p>

                    <div className="flex items-center gap-3 mt-3 flex-wrap">
                      {item.category && CATEGORY_LABELS[item.category] && (
                        <span className="text-[10px] font-black uppercase tracking-widest text-emerald-700 bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded-full">
                          {CATEGORY_LABELS[item.category]}
                        </span>
                      )}
                      <span className="text-[10px] text-gray-400">
                        {formatDate(item.created_at)}
                      </span>

                      {item.link && (
                        <Link
                          href={item.link}
                          onClick={() => !item.read_at && markRead(item.id)}
                          className="flex items-center gap-1 text-[10px] font-black text-emerald-700 hover:underline"
                        >
                          Abrir requerimento <ExternalLink size={10} />
                        </Link>
                      )}
                    </div>
                  </div>

                  {/* Botão marcar como lida */}
                  {!item.read_at && (
                    <button
                      onClick={() => markRead(item.id)}
                      title="Marcar como lida"
                      aria-label="Marcar como lida"
                      className="shrink-0 text-emerald-700 hover:text-emerald-900 p-1 rounded-lg hover:bg-emerald-50 transition"
                    >
                      <CheckCheck size={18} />
                    </button>
                  )}
                </div>
              </div>
            ))}
          </div>
        )}

        {/* Paginação */}
        {!loading && lastPage > 1 && (
          <div className="flex justify-center items-center gap-3 mt-6">
            <button
              disabled={page <= 1}
              onClick={() => setPage((p) => p - 1)}
              className="p-2 rounded-xl border border-gray-200 disabled:opacity-40 disabled:cursor-not-allowed hover:border-emerald-400 transition"
              aria-label="Página anterior"
            >
              <ChevronLeft size={18} />
            </button>
            <span className="text-sm text-gray-600 font-medium">
              {page} / {lastPage}
            </span>
            <button
              disabled={page >= lastPage}
              onClick={() => setPage((p) => p + 1)}
              className="p-2 rounded-xl border border-gray-200 disabled:opacity-40 disabled:cursor-not-allowed hover:border-emerald-400 transition"
              aria-label="Próxima página"
            >
              <ChevronRight size={18} />
            </button>
          </div>
        )}

        {/* Rodapé: links de volta */}
        <div className="flex justify-center gap-6 mt-8 text-xs text-emerald-700 font-semibold">
          <Link href="/me" className="hover:underline">← Meu perfil</Link>
          <Link href="/requests" className="hover:underline">← Meus requerimentos</Link>
        </div>
      </div>
    </div>
  );
}
