import { getErrors } from '../Utils';

export function FieldError({ errors, name }) {
    const error = getErrors(errors, name);
    if (!error) return null;
    return <p id={`${name}-error`} className="field-error">{String(error)}</p>;
}

export function FormField({ label, name, errors, hint, required = false, className = '', children, htmlFor = name }) {
    return (
        <div className={`form-group ${className}`}>
            <label className="form-label" htmlFor={htmlFor}>
                {label}{required && <span className="ml-1 text-red-600" aria-hidden="true">*</span>}
                {required && <span className="sr-only"> (required)</span>}
            </label>
            {typeof children === 'function' ? children(getErrors(errors, name)) : children}
            {hint && !getErrors(errors, name) && <p className="form-hint">{hint}</p>}
            <FieldError errors={errors} name={name} />
        </div>
    );
}

export function TextInput({ name, errors, className = '', ...props }) {
    return (
        <input
            id={props.id || name}
            name={name}
            className={`form-input ${getErrors(errors, name) ? 'form-input-error' : ''} ${className}`}
            aria-invalid={getErrors(errors, name) ? 'true' : undefined}
            aria-describedby={getErrors(errors, name) ? `${name}-error` : undefined}
            {...props}
        />
    );
}

export function SelectInput({ name, errors, children, className = '', ...props }) {
    return (
        <select
            id={props.id || name}
            name={name}
            className={`form-input ${getErrors(errors, name) ? 'form-input-error' : ''} ${className}`}
            aria-invalid={getErrors(errors, name) ? 'true' : undefined}
            aria-describedby={getErrors(errors, name) ? `${name}-error` : undefined}
            {...props}
        >
            {children}
        </select>
    );
}

export function TextArea({ name, errors, className = '', ...props }) {
    return (
        <textarea
            id={props.id || name}
            name={name}
            className={`form-input min-h-24 resize-y ${getErrors(errors, name) ? 'form-input-error' : ''} ${className}`}
            aria-invalid={getErrors(errors, name) ? 'true' : undefined}
            aria-describedby={getErrors(errors, name) ? `${name}-error` : undefined}
            {...props}
        />
    );
}
