import { CircleCheck, CircleX } from 'lucide-react'
import { cn } from '@/utils/cn'

const letters = ['A', 'B', 'C', 'D', 'E', 'F']

/**
 * A multiple-choice question. While taking the quiz it is a radio group;
 * with `review` it shows the student's choice, the correct answer and the explanation.
 */
export default function QuizQuestion({ number, question, options, value, onChange, review }) {
  const name = `question-${number}`

  return (
    <fieldset className="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
      <legend className="sr-only">Question {number}</legend>
      <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Question {number}</p>
      <p className="mt-1 font-medium leading-relaxed">{question}</p>

      <div className="mt-4 space-y-2">
        {options.map((option, index) => {
          const chosen = review ? review.chosen_option === index : value === index
          const isCorrectOption = review && review.correct_option === index
          const isWrongChoice = review && chosen && !isCorrectOption

          return (
            <label
              key={index}
              className={cn(
                'flex items-center gap-3 rounded-xl border px-3 py-2.5 text-sm transition-colors',
                review ? 'cursor-default' : 'cursor-pointer has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-brand-500',
                !review && chosen && 'border-brand-500 bg-brand-50 dark:bg-brand-950/50',
                !review && !chosen && 'border-slate-200 hover:border-slate-300 dark:border-slate-700',
                isCorrectOption && 'border-emerald-400 bg-emerald-50 dark:border-emerald-700 dark:bg-emerald-950/40',
                isWrongChoice && 'border-red-400 bg-red-50 dark:border-red-800 dark:bg-red-950/40',
                review && !isCorrectOption && !isWrongChoice && 'border-slate-200 opacity-70 dark:border-slate-700',
              )}
            >
              <input
                type="radio"
                name={name}
                className="sr-only"
                checked={chosen}
                disabled={Boolean(review)}
                onChange={() => onChange?.(index)}
              />
              <span
                className={cn(
                  'grid size-7 shrink-0 place-items-center rounded-full border text-xs font-semibold',
                  chosen && !review ? 'border-brand-600 bg-brand-600 text-white' : 'border-slate-300 dark:border-slate-600',
                )}
                aria-hidden="true"
              >
                {letters[index]}
              </span>
              <span className="flex-1">{option}</span>
              {isCorrectOption && <CircleCheck className="size-5 text-emerald-600" aria-label="Correct answer" />}
              {isWrongChoice && <CircleX className="size-5 text-red-600" aria-label="Your answer (incorrect)" />}
            </label>
          )
        })}
      </div>

      {review && (
        <div className="mt-4 space-y-1 text-sm">
          {review.chosen_option === null && <p className="font-medium text-amber-700 dark:text-amber-400">You didn’t answer this question.</p>}
          {review.explanation && (
            <p className="rounded-lg bg-slate-50 p-3 text-slate-700 dark:bg-slate-800/60 dark:text-slate-300">
              <span className="font-semibold">Explanation: </span>
              {review.explanation}
            </p>
          )}
        </div>
      )}
    </fieldset>
  )
}
