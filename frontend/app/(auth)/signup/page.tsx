import Link from 'next/link';

export default function StudentSignupUnavailablePage() {
  return <main className="min-h-screen bg-slate-50 flex items-center justify-center p-6">
    <section className="max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
      <h1 className="text-2xl font-bold text-slate-900">Conta criada pela instituição</h1>
      <p className="mt-4 text-slate-600 leading-relaxed">O cadastro de alunos não é realizado neste portal. Sua matrícula e a senha inicial são fornecidas pela instituição.</p>
      <p className="mt-3 text-sm text-slate-500">Se você ainda não recebeu acesso, procure o atendimento acadêmico responsável.</p>
      <Link href="/login" className="mt-7 inline-flex rounded-lg bg-emerald-700 px-5 py-3 font-semibold text-white hover:bg-emerald-800">Ir para o login</Link>
    </section>
  </main>;
}
