--
-- PostgreSQL database dump
--

\restrict dbXcapETEqMnh861bdNmMfdMUUsPNYKPYwwCWV9SIOjt14kFos4l0GZRL73wyiG

-- Dumped from database version 16.14 (Ubuntu 16.14-0ubuntu0.24.04.1)
-- Dumped by pg_dump version 16.14 (Ubuntu 16.14-0ubuntu0.24.04.1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: activity_log; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.activity_log (
    id bigint NOT NULL,
    log_name character varying(255),
    description text NOT NULL,
    subject_type character varying(255),
    subject_id bigint,
    causer_type character varying(255),
    causer_id bigint,
    properties json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    event character varying(255),
    batch_uuid uuid,
    module character varying(255),
    context json,
    ip_address character varying(255),
    user_agent text,
    causer_roles jsonb,
    expires_at timestamp(0) without time zone,
    retention_months integer
);


ALTER TABLE public.activity_log OWNER TO parc_info;

--
-- Name: activity_log_id_seq; Type: SEQUENCE; Schema: public; Owner: parc_info
--

CREATE SEQUENCE public.activity_log_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.activity_log_id_seq OWNER TO parc_info;

--
-- Name: activity_log_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: parc_info
--

ALTER SEQUENCE public.activity_log_id_seq OWNED BY public.activity_log.id;


--
-- Name: cache; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE public.cache OWNER TO parc_info;

--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE public.cache_locks OWNER TO parc_info;

--
-- Name: users; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    last_name character varying(255) NOT NULL,
    user_name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    service character varying(255),
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    is_active boolean DEFAULT true NOT NULL,
    avatar character varying(255),
    dossier_employe_id bigint
);


ALTER TABLE public.users OWNER TO parc_info;

--
-- Name: cores_users_id_seq; Type: SEQUENCE; Schema: public; Owner: parc_info
--

CREATE SEQUENCE public.cores_users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.cores_users_id_seq OWNER TO parc_info;

--
-- Name: cores_users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: parc_info
--

ALTER SEQUENCE public.cores_users_id_seq OWNED BY public.users.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.failed_jobs OWNER TO parc_info;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: parc_info
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.failed_jobs_id_seq OWNER TO parc_info;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: parc_info
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: grh_contacts_employes; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.grh_contacts_employes (
    id bigint NOT NULL,
    employe_id bigint NOT NULL,
    type_contact character varying(255) NOT NULL,
    valeur character varying(255) NOT NULL,
    est_whatsapp boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.grh_contacts_employes OWNER TO parc_info;

--
-- Name: COLUMN grh_contacts_employes.type_contact; Type: COMMENT; Schema: public; Owner: parc_info
--

COMMENT ON COLUMN public.grh_contacts_employes.type_contact IS 'email, telephone, etc.';


--
-- Name: grh_contacts_employes_id_seq; Type: SEQUENCE; Schema: public; Owner: parc_info
--

CREATE SEQUENCE public.grh_contacts_employes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.grh_contacts_employes_id_seq OWNER TO parc_info;

--
-- Name: grh_contacts_employes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: parc_info
--

ALTER SEQUENCE public.grh_contacts_employes_id_seq OWNED BY public.grh_contacts_employes.id;


--
-- Name: grh_dossiers_employes; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.grh_dossiers_employes (
    id bigint NOT NULL,
    matricule character varying(255) NOT NULL,
    nom character varying(255) NOT NULL,
    prenom character varying(255) NOT NULL,
    date_naissance date,
    genre character varying(255),
    date_embauche date,
    poste character varying(255),
    est_actif boolean DEFAULT true NOT NULL,
    niveau_rattachement character varying(255) NOT NULL,
    direction_id bigint,
    service_id bigint,
    unite_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT grh_dossiers_employes_genre_check CHECK (((genre)::text = ANY ((ARRAY['M'::character varying, 'F'::character varying])::text[])))
);


ALTER TABLE public.grh_dossiers_employes OWNER TO parc_info;

--
-- Name: COLUMN grh_dossiers_employes.niveau_rattachement; Type: COMMENT; Schema: public; Owner: parc_info
--

COMMENT ON COLUMN public.grh_dossiers_employes.niveau_rattachement IS 'direction, service, unite';


--
-- Name: grh_dossiers_employes_id_seq; Type: SEQUENCE; Schema: public; Owner: parc_info
--

CREATE SEQUENCE public.grh_dossiers_employes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.grh_dossiers_employes_id_seq OWNER TO parc_info;

--
-- Name: grh_dossiers_employes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: parc_info
--

ALTER SEQUENCE public.grh_dossiers_employes_id_seq OWNED BY public.grh_dossiers_employes.id;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


ALTER TABLE public.job_batches OWNER TO parc_info;

--
-- Name: jobs; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


ALTER TABLE public.jobs OWNER TO parc_info;

--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: parc_info
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.jobs_id_seq OWNER TO parc_info;

--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: parc_info
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


ALTER TABLE public.migrations OWNER TO parc_info;

--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: parc_info
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.migrations_id_seq OWNER TO parc_info;

--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: parc_info
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: model_has_permissions; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.model_has_permissions (
    permission_id bigint NOT NULL,
    model_type character varying(255) NOT NULL,
    model_id bigint NOT NULL
);


ALTER TABLE public.model_has_permissions OWNER TO parc_info;

--
-- Name: model_has_roles; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.model_has_roles (
    role_id bigint NOT NULL,
    model_type character varying(255) NOT NULL,
    model_id bigint NOT NULL
);


ALTER TABLE public.model_has_roles OWNER TO parc_info;

--
-- Name: modules; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.modules (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    slug character varying(255) NOT NULL,
    description text,
    version character varying(255) DEFAULT '1.0.0'::character varying NOT NULL,
    is_active boolean DEFAULT false NOT NULL,
    is_required boolean DEFAULT false NOT NULL,
    dependencies json,
    config json,
    icon character varying(255),
    sort_order integer DEFAULT 0 NOT NULL,
    installed_at timestamp(0) without time zone,
    activated_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


ALTER TABLE public.modules OWNER TO parc_info;

--
-- Name: modules_id_seq; Type: SEQUENCE; Schema: public; Owner: parc_info
--

CREATE SEQUENCE public.modules_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.modules_id_seq OWNER TO parc_info;

--
-- Name: modules_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: parc_info
--

ALTER SEQUENCE public.modules_id_seq OWNED BY public.modules.id;


--
-- Name: organisation_batiments; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.organisation_batiments (
    id bigint NOT NULL,
    site_id bigint NOT NULL,
    code character varying(255) NOT NULL,
    libelle character varying(255) NOT NULL,
    description text,
    nombre_etages integer,
    actif boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.organisation_batiments OWNER TO parc_info;

--
-- Name: organisation_batiments_id_seq; Type: SEQUENCE; Schema: public; Owner: parc_info
--

CREATE SEQUENCE public.organisation_batiments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.organisation_batiments_id_seq OWNER TO parc_info;

--
-- Name: organisation_batiments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: parc_info
--

ALTER SEQUENCE public.organisation_batiments_id_seq OWNED BY public.organisation_batiments.id;


--
-- Name: organisation_directions; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.organisation_directions (
    id bigint NOT NULL,
    site_id bigint NOT NULL,
    code character varying(255) NOT NULL,
    libelle character varying(255) NOT NULL,
    responsable_id bigint,
    description text,
    actif boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.organisation_directions OWNER TO parc_info;

--
-- Name: organisation_directions_id_seq; Type: SEQUENCE; Schema: public; Owner: parc_info
--

CREATE SEQUENCE public.organisation_directions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.organisation_directions_id_seq OWNER TO parc_info;

--
-- Name: organisation_directions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: parc_info
--

ALTER SEQUENCE public.organisation_directions_id_seq OWNED BY public.organisation_directions.id;


--
-- Name: organisation_etages; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.organisation_etages (
    id bigint NOT NULL,
    batiment_id bigint NOT NULL,
    numero integer NOT NULL,
    libelle character varying(255) NOT NULL,
    actif boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.organisation_etages OWNER TO parc_info;

--
-- Name: organisation_etages_id_seq; Type: SEQUENCE; Schema: public; Owner: parc_info
--

CREATE SEQUENCE public.organisation_etages_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.organisation_etages_id_seq OWNER TO parc_info;

--
-- Name: organisation_etages_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: parc_info
--

ALTER SEQUENCE public.organisation_etages_id_seq OWNED BY public.organisation_etages.id;


--
-- Name: organisation_locaux; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.organisation_locaux (
    id bigint NOT NULL,
    etage_id bigint NOT NULL,
    code character varying(255) NOT NULL,
    libelle character varying(255) NOT NULL,
    type_local character varying(255) DEFAULT 'autre'::character varying NOT NULL,
    superficie_m2 numeric(8,2),
    actif boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT organisation_locaux_type_local_check CHECK (((type_local)::text = ANY ((ARRAY['bureau'::character varying, 'salle_soins'::character varying, 'salle_attente'::character varying, 'magasin'::character varying, 'couloir'::character varying, 'autre'::character varying])::text[])))
);


ALTER TABLE public.organisation_locaux OWNER TO parc_info;

--
-- Name: organisation_locaux_id_seq; Type: SEQUENCE; Schema: public; Owner: parc_info
--

CREATE SEQUENCE public.organisation_locaux_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.organisation_locaux_id_seq OWNER TO parc_info;

--
-- Name: organisation_locaux_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: parc_info
--

ALTER SEQUENCE public.organisation_locaux_id_seq OWNED BY public.organisation_locaux.id;


--
-- Name: organisation_postes_travail; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.organisation_postes_travail (
    id bigint NOT NULL,
    code character varying(100) NOT NULL,
    libelle character varying(255) NOT NULL,
    description text,
    direction_id bigint,
    service_id bigint,
    unite_id bigint,
    local_id bigint,
    dossier_employe_id bigint,
    statut character varying(50) DEFAULT 'actif'::character varying NOT NULL,
    actif boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    niveau_rattachement character varying(50)
);


ALTER TABLE public.organisation_postes_travail OWNER TO parc_info;

--
-- Name: COLUMN organisation_postes_travail.code; Type: COMMENT; Schema: public; Owner: parc_info
--

COMMENT ON COLUMN public.organisation_postes_travail.code IS 'Format: POST-[CODE_SERVICE]-[SEQ]';


--
-- Name: COLUMN organisation_postes_travail.statut; Type: COMMENT; Schema: public; Owner: parc_info
--

COMMENT ON COLUMN public.organisation_postes_travail.statut IS 'actif, inactif, en_renovation, supprime';


--
-- Name: COLUMN organisation_postes_travail.niveau_rattachement; Type: COMMENT; Schema: public; Owner: parc_info
--

COMMENT ON COLUMN public.organisation_postes_travail.niveau_rattachement IS 'direction, service, unite';


--
-- Name: organisation_postes_travail_id_seq; Type: SEQUENCE; Schema: public; Owner: parc_info
--

CREATE SEQUENCE public.organisation_postes_travail_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.organisation_postes_travail_id_seq OWNER TO parc_info;

--
-- Name: organisation_postes_travail_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: parc_info
--

ALTER SEQUENCE public.organisation_postes_travail_id_seq OWNED BY public.organisation_postes_travail.id;


--
-- Name: organisation_services; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.organisation_services (
    id bigint NOT NULL,
    direction_id bigint NOT NULL,
    site_id bigint NOT NULL,
    code character varying(255) NOT NULL,
    libelle character varying(255) NOT NULL,
    chef_service_id bigint,
    actif boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    type_service character varying(255) NOT NULL,
    CONSTRAINT organisation_services_type_service_check CHECK (((type_service)::text = ANY ((ARRAY['administratif'::character varying, 'clinique'::character varying, 'medico-technique'::character varying])::text[])))
);


ALTER TABLE public.organisation_services OWNER TO parc_info;

--
-- Name: organisation_services_id_seq; Type: SEQUENCE; Schema: public; Owner: parc_info
--

CREATE SEQUENCE public.organisation_services_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.organisation_services_id_seq OWNER TO parc_info;

--
-- Name: organisation_services_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: parc_info
--

ALTER SEQUENCE public.organisation_services_id_seq OWNED BY public.organisation_services.id;


--
-- Name: organisation_sites; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.organisation_sites (
    id bigint NOT NULL,
    code character varying(255) NOT NULL,
    libelle character varying(255) NOT NULL,
    description text,
    adresse text,
    actif boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.organisation_sites OWNER TO parc_info;

--
-- Name: organisation_sites_id_seq; Type: SEQUENCE; Schema: public; Owner: parc_info
--

CREATE SEQUENCE public.organisation_sites_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.organisation_sites_id_seq OWNER TO parc_info;

--
-- Name: organisation_sites_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: parc_info
--

ALTER SEQUENCE public.organisation_sites_id_seq OWNED BY public.organisation_sites.id;


--
-- Name: organisation_unites; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.organisation_unites (
    id bigint NOT NULL,
    service_id bigint NOT NULL,
    site_id bigint NOT NULL,
    code character varying(255) NOT NULL,
    libelle character varying(255) NOT NULL,
    major_id bigint,
    actif boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.organisation_unites OWNER TO parc_info;

--
-- Name: organisation_unites_id_seq; Type: SEQUENCE; Schema: public; Owner: parc_info
--

CREATE SEQUENCE public.organisation_unites_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.organisation_unites_id_seq OWNER TO parc_info;

--
-- Name: organisation_unites_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: parc_info
--

ALTER SEQUENCE public.organisation_unites_id_seq OWNED BY public.organisation_unites.id;


--
-- Name: parc_info_affectation_equipements; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.parc_info_affectation_equipements (
    id bigint NOT NULL,
    code text NOT NULL,
    date_debut date NOT NULL,
    date_fin date,
    equipement_id bigint NOT NULL,
    statut boolean DEFAULT false NOT NULL,
    type_affectation text,
    type_cible text,
    dossier_employe_id bigint,
    poste_travail_id bigint,
    local_id bigint,
    niveau_rattachement text,
    direction_id bigint,
    service_id bigint,
    unite_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.parc_info_affectation_equipements OWNER TO parc_info;

--
-- Name: COLUMN parc_info_affectation_equipements.type_affectation; Type: COMMENT; Schema: public; Owner: parc_info
--

COMMENT ON COLUMN public.parc_info_affectation_equipements.type_affectation IS 'TEMPORAIRE, PERMANENTE';


--
-- Name: COLUMN parc_info_affectation_equipements.type_cible; Type: COMMENT; Schema: public; Owner: parc_info
--

COMMENT ON COLUMN public.parc_info_affectation_equipements.type_cible IS 'EMPLOYE, POSTE, LOCAL';


--
-- Name: COLUMN parc_info_affectation_equipements.niveau_rattachement; Type: COMMENT; Schema: public; Owner: parc_info
--

COMMENT ON COLUMN public.parc_info_affectation_equipements.niveau_rattachement IS 'DIRECTION, SERVICE, UNITE';


--
-- Name: parc_info_affectation_equipements_id_seq; Type: SEQUENCE; Schema: public; Owner: parc_info
--

CREATE SEQUENCE public.parc_info_affectation_equipements_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.parc_info_affectation_equipements_id_seq OWNER TO parc_info;

--
-- Name: parc_info_affectation_equipements_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: parc_info
--

ALTER SEQUENCE public.parc_info_affectation_equipements_id_seq OWNED BY public.parc_info_affectation_equipements.id;


--
-- Name: parc_info_affectations_consommables; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.parc_info_affectations_consommables (
    id bigint NOT NULL,
    consommable_id bigint NOT NULL,
    equipement_id bigint NOT NULL,
    quantite_fournie integer NOT NULL,
    date_affectation date NOT NULL,
    date_remplacement_prochain_prevu date,
    cycle_remplacement_jours integer,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.parc_info_affectations_consommables OWNER TO parc_info;

--
-- Name: parc_info_affectations_consommables_id_seq; Type: SEQUENCE; Schema: public; Owner: parc_info
--

CREATE SEQUENCE public.parc_info_affectations_consommables_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.parc_info_affectations_consommables_id_seq OWNER TO parc_info;

--
-- Name: parc_info_affectations_consommables_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: parc_info
--

ALTER SEQUENCE public.parc_info_affectations_consommables_id_seq OWNED BY public.parc_info_affectations_consommables.id;


--
-- Name: parc_info_affectations_licences; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.parc_info_affectations_licences (
    id bigint NOT NULL,
    licence_id bigint NOT NULL,
    equipement_id bigint,
    employe_id bigint,
    type_affectation character varying(255) DEFAULT 'device'::character varying NOT NULL,
    date_affectation date NOT NULL,
    date_fin_affectation date,
    actif boolean DEFAULT true NOT NULL,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT parc_info_affectations_licences_type_affectation_check CHECK (((type_affectation)::text = ANY ((ARRAY['device'::character varying, 'user'::character varying, 'concurrent'::character varying])::text[])))
);


ALTER TABLE public.parc_info_affectations_licences OWNER TO parc_info;

--
-- Name: parc_info_affectations_licences_id_seq; Type: SEQUENCE; Schema: public; Owner: parc_info
--

CREATE SEQUENCE public.parc_info_affectations_licences_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.parc_info_affectations_licences_id_seq OWNER TO parc_info;

--
-- Name: parc_info_affectations_licences_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: parc_info
--

ALTER SEQUENCE public.parc_info_affectations_licences_id_seq OWNED BY public.parc_info_affectations_licences.id;


--
-- Name: parc_info_cameras_ip; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.parc_info_cameras_ip (
    equipement_id bigint NOT NULL,
    adresse_ip inet,
    adresse_mac text,
    resolution text,
    type_camera text,
    emplacement text
);


ALTER TABLE public.parc_info_cameras_ip OWNER TO parc_info;

--
-- Name: COLUMN parc_info_cameras_ip.resolution; Type: COMMENT; Schema: public; Owner: parc_info
--

COMMENT ON COLUMN public.parc_info_cameras_ip.resolution IS 'ex: 1080p, 4K, 5MP';


--
-- Name: COLUMN parc_info_cameras_ip.type_camera; Type: COMMENT; Schema: public; Owner: parc_info
--

COMMENT ON COLUMN public.parc_info_cameras_ip.type_camera IS 'Dôme, Bullet, PTZ, etc.';


--
-- Name: parc_info_consommables; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.parc_info_consommables (
    id bigint NOT NULL,
    code character varying(255) NOT NULL,
    nom character varying(255) NOT NULL,
    type_consommable_id bigint NOT NULL,
    marque_id bigint,
    modele_reference character varying(255),
    compatible_equipements json,
    fournisseur_principal_id bigint NOT NULL,
    cout_unitaire numeric(10,2) NOT NULL,
    quantite_stock_actuel integer DEFAULT 0 NOT NULL,
    quantite_stock_min integer DEFAULT 5 NOT NULL,
    quantite_stock_max integer DEFAULT 50 NOT NULL,
    stock_reserve_maintenance integer DEFAULT 0 NOT NULL,
    date_dernier_approvisionnement date,
    est_actif boolean DEFAULT true NOT NULL,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.parc_info_consommables OWNER TO parc_info;

--
-- Name: parc_info_consommables_id_seq; Type: SEQUENCE; Schema: public; Owner: parc_info
--

CREATE SEQUENCE public.parc_info_consommables_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.parc_info_consommables_id_seq OWNER TO parc_info;

--
-- Name: parc_info_consommables_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: parc_info
--

ALTER SEQUENCE public.parc_info_consommables_id_seq OWNED BY public.parc_info_consommables.id;


--
-- Name: parc_info_contacts; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.parc_info_contacts (
    id bigint NOT NULL,
    nom character varying(255) NOT NULL,
    prenom character varying(255),
    email character varying(255),
    telephone character varying(255),
    fonction character varying(255),
    est_actif boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    fournisseur_id bigint
);


ALTER TABLE public.parc_info_contacts OWNER TO parc_info;

--
-- Name: parc_info_contacts_id_seq; Type: SEQUENCE; Schema: public; Owner: parc_info
--

CREATE SEQUENCE public.parc_info_contacts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.parc_info_contacts_id_seq OWNER TO parc_info;

--
-- Name: parc_info_contacts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: parc_info
--

ALTER SEQUENCE public.parc_info_contacts_id_seq OWNED BY public.parc_info_contacts.id;


--
-- Name: parc_info_contrats_maintenances; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.parc_info_contrats_maintenances (
    id bigint NOT NULL,
    reference character varying(255) NOT NULL,
    nom character varying(255),
    fournisseur_id bigint,
    date_debut date,
    date_fin date,
    cout numeric(10,2),
    est_actif boolean DEFAULT true NOT NULL,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.parc_info_contrats_maintenances OWNER TO parc_info;

--
-- Name: parc_info_contrats_maintenances_id_seq; Type: SEQUENCE; Schema: public; Owner: parc_info
--

CREATE SEQUENCE public.parc_info_contrats_maintenances_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.parc_info_contrats_maintenances_id_seq OWNER TO parc_info;

--
-- Name: parc_info_contrats_maintenances_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: parc_info
--

ALTER SEQUENCE public.parc_info_contrats_maintenances_id_seq OWNED BY public.parc_info_contrats_maintenances.id;


--
-- Name: parc_info_documents_licences; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.parc_info_documents_licences (
    id bigint NOT NULL,
    licence_id bigint NOT NULL,
    type character varying(255) NOT NULL,
    nom_fichier character varying(255) NOT NULL,
    chemin_stockage character varying(255) NOT NULL,
    date_document date,
    description text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT parc_info_documents_licences_type_check CHECK (((type)::text = ANY ((ARRAY['Facture'::character varying, 'Certificat'::character varying, 'Contrat'::character varying, 'Cle'::character varying, 'Proof of License'::character varying])::text[])))
);


ALTER TABLE public.parc_info_documents_licences OWNER TO parc_info;

--
-- Name: parc_info_documents_licences_id_seq; Type: SEQUENCE; Schema: public; Owner: parc_info
--

CREATE SEQUENCE public.parc_info_documents_licences_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.parc_info_documents_licences_id_seq OWNER TO parc_info;

--
-- Name: parc_info_documents_licences_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: parc_info
--

ALTER SEQUENCE public.parc_info_documents_licences_id_seq OWNED BY public.parc_info_documents_licences.id;


--
-- Name: parc_info_editeurs; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.parc_info_editeurs (
    id bigint NOT NULL,
    code character varying(255) NOT NULL,
    nom character varying(255) NOT NULL,
    logo_url character varying(255),
    site_web character varying(255),
    email_support character varying(255),
    telephone_support character varying(255),
    est_actif boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.parc_info_editeurs OWNER TO parc_info;

--
-- Name: parc_info_editeurs_id_seq; Type: SEQUENCE; Schema: public; Owner: parc_info
--

CREATE SEQUENCE public.parc_info_editeurs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.parc_info_editeurs_id_seq OWNER TO parc_info;

--
-- Name: parc_info_editeurs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: parc_info
--

ALTER SEQUENCE public.parc_info_editeurs_id_seq OWNED BY public.parc_info_editeurs.id;


--
-- Name: parc_info_equipements; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.parc_info_equipements (
    id bigint NOT NULL,
    code_inventaire text NOT NULL,
    numero_serie text NOT NULL,
    marque_id bigint,
    modele text NOT NULL,
    date_acquisition date,
    date_mise_en_service date,
    valeur_achat numeric(12,2),
    duree_vie_probable integer,
    date_fin_garantie date,
    statut text NOT NULL,
    etat text NOT NULL,
    tags json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    ref_bordereau character varying(255),
    direction_id bigint,
    service_id bigint,
    unite_id bigint
);


ALTER TABLE public.parc_info_equipements OWNER TO parc_info;

--
-- Name: COLUMN parc_info_equipements.duree_vie_probable; Type: COMMENT; Schema: public; Owner: parc_info
--

COMMENT ON COLUMN public.parc_info_equipements.duree_vie_probable IS 'en années';


--
-- Name: COLUMN parc_info_equipements.statut; Type: COMMENT; Schema: public; Owner: parc_info
--

COMMENT ON COLUMN public.parc_info_equipements.statut IS 'en_stock, en_service, en_reparation, perdu, reforme';


--
-- Name: COLUMN parc_info_equipements.etat; Type: COMMENT; Schema: public; Owner: parc_info
--

COMMENT ON COLUMN public.parc_info_equipements.etat IS 'bon, passable, mauvais, avarie';


--
-- Name: parc_info_equipements_id_seq; Type: SEQUENCE; Schema: public; Owner: parc_info
--

CREATE SEQUENCE public.parc_info_equipements_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.parc_info_equipements_id_seq OWNER TO parc_info;

--
-- Name: parc_info_equipements_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: parc_info
--

ALTER SEQUENCE public.parc_info_equipements_id_seq OWNED BY public.parc_info_equipements.id;


--
-- Name: parc_info_equipements_reseaux; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.parc_info_equipements_reseaux (
    equipement_id bigint NOT NULL,
    type_reseau_id bigint,
    nb_ports integer,
    vitesse_max_mbps integer,
    est_poe boolean DEFAULT false NOT NULL,
    version_firmware text,
    u_position_depart integer,
    u_position_fin integer,
    vlan_management integer,
    adresse_ip inet,
    masque_sous_reseau inet,
    passerelle inet,
    communaute_snmp text,
    est_manageable boolean DEFAULT true NOT NULL
);


ALTER TABLE public.parc_info_equipements_reseaux OWNER TO parc_info;

--
-- Name: parc_info_fournisseurs; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.parc_info_fournisseurs (
    id bigint NOT NULL,
    code character varying(255) NOT NULL,
    nom character varying(255) NOT NULL,
    type character varying(255),
    email character varying(255),
    telephone character varying(255),
    adresse text,
    contact_principal_id bigint,
    conditions_paiement character varying(255),
    delai_livraison character varying(255),
    fiabilite_score integer,
    est_actif boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    code_postal character varying(10),
    ville character varying(100),
    pays character varying(100)
);


ALTER TABLE public.parc_info_fournisseurs OWNER TO parc_info;

--
-- Name: parc_info_fournisseurs_id_seq; Type: SEQUENCE; Schema: public; Owner: parc_info
--

CREATE SEQUENCE public.parc_info_fournisseurs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.parc_info_fournisseurs_id_seq OWNER TO parc_info;

--
-- Name: parc_info_fournisseurs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: parc_info
--

ALTER SEQUENCE public.parc_info_fournisseurs_id_seq OWNED BY public.parc_info_fournisseurs.id;


--
-- Name: parc_info_historique_changements; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.parc_info_historique_changements (
    id bigint NOT NULL,
    equipement_id bigint NOT NULL,
    date_changement timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    utilisateur_id bigint,
    type_changement text NOT NULL,
    ancien_statut text,
    nouveau_statut text,
    ancien_etat text,
    nouvel_etat text,
    motif text NOT NULL,
    reference_document text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.parc_info_historique_changements OWNER TO parc_info;

--
-- Name: COLUMN parc_info_historique_changements.type_changement; Type: COMMENT; Schema: public; Owner: parc_info
--

COMMENT ON COLUMN public.parc_info_historique_changements.type_changement IS 'STATUT, ETAT, AFFECTATION, TECHNIQUE';


--
-- Name: COLUMN parc_info_historique_changements.reference_document; Type: COMMENT; Schema: public; Owner: parc_info
--

COMMENT ON COLUMN public.parc_info_historique_changements.reference_document IS 'PV, BMD, BST';


--
-- Name: parc_info_historique_changements_id_seq; Type: SEQUENCE; Schema: public; Owner: parc_info
--

CREATE SEQUENCE public.parc_info_historique_changements_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.parc_info_historique_changements_id_seq OWNER TO parc_info;

--
-- Name: parc_info_historique_changements_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: parc_info
--

ALTER SEQUENCE public.parc_info_historique_changements_id_seq OWNED BY public.parc_info_historique_changements.id;


--
-- Name: parc_info_imprimantes; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.parc_info_imprimantes (
    equipement_id bigint NOT NULL,
    type_imprimante_id bigint,
    est_couleur boolean DEFAULT false NOT NULL,
    est_multifonction boolean DEFAULT false NOT NULL,
    fonctions text,
    adresse_ip inet,
    snmp_community text
);


ALTER TABLE public.parc_info_imprimantes OWNER TO parc_info;

--
-- Name: COLUMN parc_info_imprimantes.fonctions; Type: COMMENT; Schema: public; Owner: parc_info
--

COMMENT ON COLUMN public.parc_info_imprimantes.fonctions IS 'Scan, Print, Copy, Fax';


--
-- Name: parc_info_infrastructures; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.parc_info_infrastructures (
    equipement_id bigint NOT NULL,
    type_infra_id bigint,
    puissance_va integer,
    autonomie_minutes integer,
    date_dernier_remplacement_batterie date,
    nb_prises_pdu integer,
    u_capacite_totale integer,
    est_redondant boolean DEFAULT false NOT NULL,
    nb_ports integer,
    categorie_cable character varying(50),
    type_connecteur character varying(50),
    u_taille integer
);


ALTER TABLE public.parc_info_infrastructures OWNER TO parc_info;

--
-- Name: COLUMN parc_info_infrastructures.puissance_va; Type: COMMENT; Schema: public; Owner: parc_info
--

COMMENT ON COLUMN public.parc_info_infrastructures.puissance_va IS 'Pour les onduleurs';


--
-- Name: COLUMN parc_info_infrastructures.u_capacite_totale; Type: COMMENT; Schema: public; Owner: parc_info
--

COMMENT ON COLUMN public.parc_info_infrastructures.u_capacite_totale IS 'Pour les racks, ex: 42U';


--
-- Name: COLUMN parc_info_infrastructures.nb_ports; Type: COMMENT; Schema: public; Owner: parc_info
--

COMMENT ON COLUMN public.parc_info_infrastructures.nb_ports IS 'Nombre de ports (ex: 24, 48) — Brassage';


--
-- Name: COLUMN parc_info_infrastructures.categorie_cable; Type: COMMENT; Schema: public; Owner: parc_info
--

COMMENT ON COLUMN public.parc_info_infrastructures.categorie_cable IS 'Cat5e, Cat6, Cat6A, Fibre — Brassage';


--
-- Name: COLUMN parc_info_infrastructures.type_connecteur; Type: COMMENT; Schema: public; Owner: parc_info
--

COMMENT ON COLUMN public.parc_info_infrastructures.type_connecteur IS 'RJ45, LC, SC — Brassage';


--
-- Name: COLUMN parc_info_infrastructures.u_taille; Type: COMMENT; Schema: public; Owner: parc_info
--

COMMENT ON COLUMN public.parc_info_infrastructures.u_taille IS 'Taille en U (ex: 1 ou 2) — Brassage';


--
-- Name: parc_info_licences; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.parc_info_licences (
    id bigint NOT NULL,
    logiciel_id bigint NOT NULL,
    cle_licence character varying(255),
    numero_contrat character varying(255),
    contrat_maintenance_id bigint,
    type_activation character varying(255) DEFAULT 'volume'::character varying NOT NULL,
    modele_licencing character varying(255) DEFAULT 'device'::character varying NOT NULL,
    nombre_postes_accordes integer DEFAULT 1 NOT NULL,
    nombre_postes_utilises integer DEFAULT 0 NOT NULL,
    date_acquisition date NOT NULL,
    date_activation date,
    date_expiration date NOT NULL,
    date_renouvellement_prochain date,
    cout_unitaire numeric(10,2),
    cout_total numeric(12,2),
    devise character varying(3) DEFAULT 'EUR'::character varying NOT NULL,
    fournisseur_id bigint NOT NULL,
    contact_support_id bigint,
    statut character varying(255) DEFAULT 'actif'::character varying NOT NULL,
    conditions_utilisation character varying(255),
    actif boolean DEFAULT true NOT NULL,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT parc_info_licences_modele_licencing_check CHECK (((modele_licencing)::text = ANY ((ARRAY['device'::character varying, 'user'::character varying, 'concurrent'::character varying, 'named'::character varying])::text[]))),
    CONSTRAINT parc_info_licences_statut_check CHECK (((statut)::text = ANY ((ARRAY['actif'::character varying, 'expire'::character varying, 'en_renouvellement'::character varying, 'suspendu'::character varying])::text[]))),
    CONSTRAINT parc_info_licences_type_activation_check CHECK (((type_activation)::text = ANY ((ARRAY['volume'::character varying, 'concurrent'::character varying, 'subscription'::character varying, 'free'::character varying])::text[])))
);


ALTER TABLE public.parc_info_licences OWNER TO parc_info;

--
-- Name: parc_info_licences_id_seq; Type: SEQUENCE; Schema: public; Owner: parc_info
--

CREATE SEQUENCE public.parc_info_licences_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.parc_info_licences_id_seq OWNER TO parc_info;

--
-- Name: parc_info_licences_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: parc_info
--

ALTER SEQUENCE public.parc_info_licences_id_seq OWNED BY public.parc_info_licences.id;


--
-- Name: parc_info_logiciels; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.parc_info_logiciels (
    id bigint NOT NULL,
    code character varying(255) NOT NULL,
    nom character varying(255) NOT NULL,
    description text,
    type_licence_id bigint NOT NULL,
    editeur_id bigint NOT NULL,
    categorie character varying(255),
    est_actif boolean DEFAULT true NOT NULL,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.parc_info_logiciels OWNER TO parc_info;

--
-- Name: parc_info_logiciels_id_seq; Type: SEQUENCE; Schema: public; Owner: parc_info
--

CREATE SEQUENCE public.parc_info_logiciels_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.parc_info_logiciels_id_seq OWNER TO parc_info;

--
-- Name: parc_info_logiciels_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: parc_info
--

ALTER SEQUENCE public.parc_info_logiciels_id_seq OWNED BY public.parc_info_logiciels.id;


--
-- Name: parc_info_marques; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.parc_info_marques (
    id bigint NOT NULL,
    libelle text NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.parc_info_marques OWNER TO parc_info;

--
-- Name: parc_info_marques_id_seq; Type: SEQUENCE; Schema: public; Owner: parc_info
--

CREATE SEQUENCE public.parc_info_marques_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.parc_info_marques_id_seq OWNER TO parc_info;

--
-- Name: parc_info_marques_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: parc_info
--

ALTER SEQUENCE public.parc_info_marques_id_seq OWNED BY public.parc_info_marques.id;


--
-- Name: parc_info_mobiles; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.parc_info_mobiles (
    equipement_id bigint NOT NULL,
    type_mobile_id bigint,
    imei_1 text,
    imei_2 text,
    num_tel_associe text,
    version_os text,
    statut_mdm text,
    capacite_batterie_mah integer,
    etat_ecran text,
    a_coque_protection boolean DEFAULT true NOT NULL
);


ALTER TABLE public.parc_info_mobiles OWNER TO parc_info;

--
-- Name: COLUMN parc_info_mobiles.statut_mdm; Type: COMMENT; Schema: public; Owner: parc_info
--

COMMENT ON COLUMN public.parc_info_mobiles.statut_mdm IS 'Enrôlé, Non enrôlé';


--
-- Name: parc_info_mouvements_consommables; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.parc_info_mouvements_consommables (
    id bigint NOT NULL,
    consommable_id bigint NOT NULL,
    type_mouvement character varying(255) NOT NULL,
    quantite integer NOT NULL,
    prix_unitaire numeric(10,2),
    date_mouvement timestamp(0) without time zone NOT NULL,
    reference_commande character varying(255),
    utilisateur_id bigint NOT NULL,
    equipement_id bigint,
    employe_id bigint,
    raison character varying(255),
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    service_id bigint,
    unite_id bigint,
    CONSTRAINT parc_info_mouvements_consommables_type_mouvement_check CHECK (((type_mouvement)::text = ANY ((ARRAY['Achat'::character varying, 'Consommation'::character varying, 'Retour'::character varying, 'Ajustement'::character varying, 'Maintenance'::character varying])::text[])))
);


ALTER TABLE public.parc_info_mouvements_consommables OWNER TO parc_info;

--
-- Name: parc_info_mouvements_consommables_id_seq; Type: SEQUENCE; Schema: public; Owner: parc_info
--

CREATE SEQUENCE public.parc_info_mouvements_consommables_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.parc_info_mouvements_consommables_id_seq OWNER TO parc_info;

--
-- Name: parc_info_mouvements_consommables_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: parc_info
--

ALTER SEQUENCE public.parc_info_mouvements_consommables_id_seq OWNED BY public.parc_info_mouvements_consommables.id;


--
-- Name: parc_info_ordinateurs; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.parc_info_ordinateurs (
    equipement_id bigint NOT NULL,
    type_pc text NOT NULL,
    ram_type_id bigint,
    ram_capacite_go integer,
    cpu_type_id bigint,
    processeur_model text,
    disque_type_id bigint,
    stockage_capacite_go integer,
    os_type_id bigint,
    licence_windows_type text,
    licence_windows_cle text,
    licence_office_type text,
    licence_office_cle text,
    support_tpm2 boolean DEFAULT false NOT NULL,
    support_secure_boot boolean DEFAULT false NOT NULL,
    bios_version text,
    uefi_version text,
    nom_hote text,
    compte_admin_local text,
    domaine_workgroup text,
    adresse_mac_wifi text,
    adresse_mac_ethernet text,
    cycle_batterie integer
);


ALTER TABLE public.parc_info_ordinateurs OWNER TO parc_info;

--
-- Name: COLUMN parc_info_ordinateurs.type_pc; Type: COMMENT; Schema: public; Owner: parc_info
--

COMMENT ON COLUMN public.parc_info_ordinateurs.type_pc IS 'Portable, Fixe, Workstation';


--
-- Name: COLUMN parc_info_ordinateurs.licence_windows_type; Type: COMMENT; Schema: public; Owner: parc_info
--

COMMENT ON COLUMN public.parc_info_ordinateurs.licence_windows_type IS 'OEM, CLE, AUCUNE';


--
-- Name: COLUMN parc_info_ordinateurs.licence_office_type; Type: COMMENT; Schema: public; Owner: parc_info
--

COMMENT ON COLUMN public.parc_info_ordinateurs.licence_office_type IS 'CLE, AUCUNE';


--
-- Name: COLUMN parc_info_ordinateurs.cycle_batterie; Type: COMMENT; Schema: public; Owner: parc_info
--

COMMENT ON COLUMN public.parc_info_ordinateurs.cycle_batterie IS 'Pour les portables';


--
-- Name: parc_info_scanners; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.parc_info_scanners (
    equipement_id bigint NOT NULL,
    resolution_dpi_max integer,
    format_max text,
    est_recto_verso boolean DEFAULT false NOT NULL,
    a_chargeur_auto boolean DEFAULT false NOT NULL,
    type_capteur text,
    adresse_ip inet,
    interface_connexion character varying(255)
);


ALTER TABLE public.parc_info_scanners OWNER TO parc_info;

--
-- Name: COLUMN parc_info_scanners.format_max; Type: COMMENT; Schema: public; Owner: parc_info
--

COMMENT ON COLUMN public.parc_info_scanners.format_max IS 'A4, A3';


--
-- Name: COLUMN parc_info_scanners.type_capteur; Type: COMMENT; Schema: public; Owner: parc_info
--

COMMENT ON COLUMN public.parc_info_scanners.type_capteur IS 'CIS, CCD';


--
-- Name: parc_info_serveurs; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.parc_info_serveurs (
    equipement_id bigint NOT NULL,
    role_serveur text,
    ram_type_id bigint,
    ram_capacite_go integer,
    cpu_type_id bigint,
    nb_processeurs integer,
    nb_coeurs_total integer,
    disque_type_id bigint,
    stockage_capacite_go integer,
    os_type_id bigint,
    nom_hote text,
    domaine text,
    adresse_ip inet,
    adresse_mac text,
    hyperviseur text,
    u_position_depart integer,
    u_position_fin integer
);


ALTER TABLE public.parc_info_serveurs OWNER TO parc_info;

--
-- Name: COLUMN parc_info_serveurs.role_serveur; Type: COMMENT; Schema: public; Owner: parc_info
--

COMMENT ON COLUMN public.parc_info_serveurs.role_serveur IS 'Application, Base de données, Fichiers, Web, AD/DC';


--
-- Name: COLUMN parc_info_serveurs.hyperviseur; Type: COMMENT; Schema: public; Owner: parc_info
--

COMMENT ON COLUMN public.parc_info_serveurs.hyperviseur IS 'VMware ESXi, Hyper-V, Proxmox';


--
-- Name: parc_info_serveurs_virtuels; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.parc_info_serveurs_virtuels (
    equipement_id bigint NOT NULL,
    role_serveur text,
    ram_type_id bigint,
    ram_capacite_go integer,
    cpu_type_id bigint,
    nb_processeurs integer,
    nb_coeurs_total integer,
    disque_type_id bigint,
    stockage_capacite_go integer,
    os_type_id bigint,
    nom_hote text,
    domaine text,
    adresse_ip inet,
    adresse_mac text,
    hyperviseur text,
    serveur_hote_id bigint
);


ALTER TABLE public.parc_info_serveurs_virtuels OWNER TO parc_info;

--
-- Name: parc_info_telephones; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.parc_info_telephones (
    equipement_id bigint NOT NULL,
    est_ip boolean DEFAULT true NOT NULL,
    extension text,
    protocole text,
    adresse_mac_ethernet text,
    adresse_ip inet,
    modele_expansion_count integer DEFAULT 0 NOT NULL
);


ALTER TABLE public.parc_info_telephones OWNER TO parc_info;

--
-- Name: COLUMN parc_info_telephones.protocole; Type: COMMENT; Schema: public; Owner: parc_info
--

COMMENT ON COLUMN public.parc_info_telephones.protocole IS 'SIP, H.323, SCCP';


--
-- Name: parc_info_types_consommables; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.parc_info_types_consommables (
    id bigint NOT NULL,
    code character varying(255) NOT NULL,
    nom character varying(255) NOT NULL,
    categorie character varying(255) NOT NULL,
    sous_categorie character varying(255),
    unite_stock character varying(255) NOT NULL,
    seul_reapprovisionnement integer DEFAULT 5 NOT NULL,
    duree_conservation_jours integer,
    description text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT parc_info_types_consommables_categorie_check CHECK (((categorie)::text = ANY ((ARRAY['Impression'::character varying, 'Fournitures Bureau'::character varying, 'Maintenance'::character varying, 'Reseau'::character varying, 'Securite'::character varying, 'Accessoires'::character varying])::text[])))
);


ALTER TABLE public.parc_info_types_consommables OWNER TO parc_info;

--
-- Name: parc_info_types_consommables_id_seq; Type: SEQUENCE; Schema: public; Owner: parc_info
--

CREATE SEQUENCE public.parc_info_types_consommables_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.parc_info_types_consommables_id_seq OWNER TO parc_info;

--
-- Name: parc_info_types_consommables_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: parc_info
--

ALTER SEQUENCE public.parc_info_types_consommables_id_seq OWNED BY public.parc_info_types_consommables.id;


--
-- Name: parc_info_types_cpus; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.parc_info_types_cpus (
    id bigint NOT NULL,
    libelle text NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.parc_info_types_cpus OWNER TO parc_info;

--
-- Name: parc_info_types_cpus_id_seq; Type: SEQUENCE; Schema: public; Owner: parc_info
--

CREATE SEQUENCE public.parc_info_types_cpus_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.parc_info_types_cpus_id_seq OWNER TO parc_info;

--
-- Name: parc_info_types_cpus_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: parc_info
--

ALTER SEQUENCE public.parc_info_types_cpus_id_seq OWNED BY public.parc_info_types_cpus.id;


--
-- Name: parc_info_types_disques; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.parc_info_types_disques (
    id bigint NOT NULL,
    libelle text NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.parc_info_types_disques OWNER TO parc_info;

--
-- Name: parc_info_types_disques_id_seq; Type: SEQUENCE; Schema: public; Owner: parc_info
--

CREATE SEQUENCE public.parc_info_types_disques_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.parc_info_types_disques_id_seq OWNER TO parc_info;

--
-- Name: parc_info_types_disques_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: parc_info
--

ALTER SEQUENCE public.parc_info_types_disques_id_seq OWNED BY public.parc_info_types_disques.id;


--
-- Name: parc_info_types_imprimantes; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.parc_info_types_imprimantes (
    id bigint NOT NULL,
    libelle text NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.parc_info_types_imprimantes OWNER TO parc_info;

--
-- Name: parc_info_types_imprimantes_id_seq; Type: SEQUENCE; Schema: public; Owner: parc_info
--

CREATE SEQUENCE public.parc_info_types_imprimantes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.parc_info_types_imprimantes_id_seq OWNER TO parc_info;

--
-- Name: parc_info_types_imprimantes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: parc_info
--

ALTER SEQUENCE public.parc_info_types_imprimantes_id_seq OWNED BY public.parc_info_types_imprimantes.id;


--
-- Name: parc_info_types_infrastructures; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.parc_info_types_infrastructures (
    id bigint NOT NULL,
    libelle text NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.parc_info_types_infrastructures OWNER TO parc_info;

--
-- Name: parc_info_types_infrastructures_id_seq; Type: SEQUENCE; Schema: public; Owner: parc_info
--

CREATE SEQUENCE public.parc_info_types_infrastructures_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.parc_info_types_infrastructures_id_seq OWNER TO parc_info;

--
-- Name: parc_info_types_infrastructures_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: parc_info
--

ALTER SEQUENCE public.parc_info_types_infrastructures_id_seq OWNED BY public.parc_info_types_infrastructures.id;


--
-- Name: parc_info_types_licences; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.parc_info_types_licences (
    id bigint NOT NULL,
    code character varying(255) NOT NULL,
    libelle character varying(255) NOT NULL,
    description text,
    modeles json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.parc_info_types_licences OWNER TO parc_info;

--
-- Name: parc_info_types_licences_id_seq; Type: SEQUENCE; Schema: public; Owner: parc_info
--

CREATE SEQUENCE public.parc_info_types_licences_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.parc_info_types_licences_id_seq OWNER TO parc_info;

--
-- Name: parc_info_types_licences_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: parc_info
--

ALTER SEQUENCE public.parc_info_types_licences_id_seq OWNED BY public.parc_info_types_licences.id;


--
-- Name: parc_info_types_mobiles; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.parc_info_types_mobiles (
    id bigint NOT NULL,
    libelle text NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.parc_info_types_mobiles OWNER TO parc_info;

--
-- Name: parc_info_types_mobiles_id_seq; Type: SEQUENCE; Schema: public; Owner: parc_info
--

CREATE SEQUENCE public.parc_info_types_mobiles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.parc_info_types_mobiles_id_seq OWNER TO parc_info;

--
-- Name: parc_info_types_mobiles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: parc_info
--

ALTER SEQUENCE public.parc_info_types_mobiles_id_seq OWNED BY public.parc_info_types_mobiles.id;


--
-- Name: parc_info_types_os; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.parc_info_types_os (
    id bigint NOT NULL,
    libelle text NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.parc_info_types_os OWNER TO parc_info;

--
-- Name: parc_info_types_os_id_seq; Type: SEQUENCE; Schema: public; Owner: parc_info
--

CREATE SEQUENCE public.parc_info_types_os_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.parc_info_types_os_id_seq OWNER TO parc_info;

--
-- Name: parc_info_types_os_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: parc_info
--

ALTER SEQUENCE public.parc_info_types_os_id_seq OWNED BY public.parc_info_types_os.id;


--
-- Name: parc_info_types_rams; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.parc_info_types_rams (
    id bigint NOT NULL,
    libelle text NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.parc_info_types_rams OWNER TO parc_info;

--
-- Name: parc_info_types_rams_id_seq; Type: SEQUENCE; Schema: public; Owner: parc_info
--

CREATE SEQUENCE public.parc_info_types_rams_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.parc_info_types_rams_id_seq OWNER TO parc_info;

--
-- Name: parc_info_types_rams_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: parc_info
--

ALTER SEQUENCE public.parc_info_types_rams_id_seq OWNED BY public.parc_info_types_rams.id;


--
-- Name: parc_info_types_reseaux; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.parc_info_types_reseaux (
    id bigint NOT NULL,
    libelle text NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.parc_info_types_reseaux OWNER TO parc_info;

--
-- Name: parc_info_types_reseaux_id_seq; Type: SEQUENCE; Schema: public; Owner: parc_info
--

CREATE SEQUENCE public.parc_info_types_reseaux_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.parc_info_types_reseaux_id_seq OWNER TO parc_info;

--
-- Name: parc_info_types_reseaux_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: parc_info
--

ALTER SEQUENCE public.parc_info_types_reseaux_id_seq OWNED BY public.parc_info_types_reseaux.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


ALTER TABLE public.password_reset_tokens OWNER TO parc_info;

--
-- Name: permissions; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.permissions (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    guard_name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    label character varying(255),
    module character varying(255),
    category character varying(255),
    description text,
    "group" character varying(255),
    sort_order integer DEFAULT 0 NOT NULL,
    is_visible boolean DEFAULT true NOT NULL
);


ALTER TABLE public.permissions OWNER TO parc_info;

--
-- Name: permissions_id_seq; Type: SEQUENCE; Schema: public; Owner: parc_info
--

CREATE SEQUENCE public.permissions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.permissions_id_seq OWNER TO parc_info;

--
-- Name: permissions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: parc_info
--

ALTER SEQUENCE public.permissions_id_seq OWNED BY public.permissions.id;


--
-- Name: role_has_permissions; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.role_has_permissions (
    permission_id bigint NOT NULL,
    role_id bigint NOT NULL
);


ALTER TABLE public.role_has_permissions OWNER TO parc_info;

--
-- Name: roles; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.roles (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    guard_name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    description character varying(255)
);


ALTER TABLE public.roles OWNER TO parc_info;

--
-- Name: roles_id_seq; Type: SEQUENCE; Schema: public; Owner: parc_info
--

CREATE SEQUENCE public.roles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.roles_id_seq OWNER TO parc_info;

--
-- Name: roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: parc_info
--

ALTER SEQUENCE public.roles_id_seq OWNED BY public.roles.id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: parc_info
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


ALTER TABLE public.sessions OWNER TO parc_info;

--
-- Name: activity_log id; Type: DEFAULT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.activity_log ALTER COLUMN id SET DEFAULT nextval('public.activity_log_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: grh_contacts_employes id; Type: DEFAULT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.grh_contacts_employes ALTER COLUMN id SET DEFAULT nextval('public.grh_contacts_employes_id_seq'::regclass);


--
-- Name: grh_dossiers_employes id; Type: DEFAULT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.grh_dossiers_employes ALTER COLUMN id SET DEFAULT nextval('public.grh_dossiers_employes_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: modules id; Type: DEFAULT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.modules ALTER COLUMN id SET DEFAULT nextval('public.modules_id_seq'::regclass);


--
-- Name: organisation_batiments id; Type: DEFAULT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.organisation_batiments ALTER COLUMN id SET DEFAULT nextval('public.organisation_batiments_id_seq'::regclass);


--
-- Name: organisation_directions id; Type: DEFAULT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.organisation_directions ALTER COLUMN id SET DEFAULT nextval('public.organisation_directions_id_seq'::regclass);


--
-- Name: organisation_etages id; Type: DEFAULT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.organisation_etages ALTER COLUMN id SET DEFAULT nextval('public.organisation_etages_id_seq'::regclass);


--
-- Name: organisation_locaux id; Type: DEFAULT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.organisation_locaux ALTER COLUMN id SET DEFAULT nextval('public.organisation_locaux_id_seq'::regclass);


--
-- Name: organisation_postes_travail id; Type: DEFAULT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.organisation_postes_travail ALTER COLUMN id SET DEFAULT nextval('public.organisation_postes_travail_id_seq'::regclass);


--
-- Name: organisation_services id; Type: DEFAULT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.organisation_services ALTER COLUMN id SET DEFAULT nextval('public.organisation_services_id_seq'::regclass);


--
-- Name: organisation_sites id; Type: DEFAULT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.organisation_sites ALTER COLUMN id SET DEFAULT nextval('public.organisation_sites_id_seq'::regclass);


--
-- Name: organisation_unites id; Type: DEFAULT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.organisation_unites ALTER COLUMN id SET DEFAULT nextval('public.organisation_unites_id_seq'::regclass);


--
-- Name: parc_info_affectation_equipements id; Type: DEFAULT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_affectation_equipements ALTER COLUMN id SET DEFAULT nextval('public.parc_info_affectation_equipements_id_seq'::regclass);


--
-- Name: parc_info_affectations_consommables id; Type: DEFAULT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_affectations_consommables ALTER COLUMN id SET DEFAULT nextval('public.parc_info_affectations_consommables_id_seq'::regclass);


--
-- Name: parc_info_affectations_licences id; Type: DEFAULT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_affectations_licences ALTER COLUMN id SET DEFAULT nextval('public.parc_info_affectations_licences_id_seq'::regclass);


--
-- Name: parc_info_consommables id; Type: DEFAULT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_consommables ALTER COLUMN id SET DEFAULT nextval('public.parc_info_consommables_id_seq'::regclass);


--
-- Name: parc_info_contacts id; Type: DEFAULT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_contacts ALTER COLUMN id SET DEFAULT nextval('public.parc_info_contacts_id_seq'::regclass);


--
-- Name: parc_info_contrats_maintenances id; Type: DEFAULT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_contrats_maintenances ALTER COLUMN id SET DEFAULT nextval('public.parc_info_contrats_maintenances_id_seq'::regclass);


--
-- Name: parc_info_documents_licences id; Type: DEFAULT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_documents_licences ALTER COLUMN id SET DEFAULT nextval('public.parc_info_documents_licences_id_seq'::regclass);


--
-- Name: parc_info_editeurs id; Type: DEFAULT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_editeurs ALTER COLUMN id SET DEFAULT nextval('public.parc_info_editeurs_id_seq'::regclass);


--
-- Name: parc_info_equipements id; Type: DEFAULT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_equipements ALTER COLUMN id SET DEFAULT nextval('public.parc_info_equipements_id_seq'::regclass);


--
-- Name: parc_info_fournisseurs id; Type: DEFAULT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_fournisseurs ALTER COLUMN id SET DEFAULT nextval('public.parc_info_fournisseurs_id_seq'::regclass);


--
-- Name: parc_info_historique_changements id; Type: DEFAULT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_historique_changements ALTER COLUMN id SET DEFAULT nextval('public.parc_info_historique_changements_id_seq'::regclass);


--
-- Name: parc_info_licences id; Type: DEFAULT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_licences ALTER COLUMN id SET DEFAULT nextval('public.parc_info_licences_id_seq'::regclass);


--
-- Name: parc_info_logiciels id; Type: DEFAULT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_logiciels ALTER COLUMN id SET DEFAULT nextval('public.parc_info_logiciels_id_seq'::regclass);


--
-- Name: parc_info_marques id; Type: DEFAULT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_marques ALTER COLUMN id SET DEFAULT nextval('public.parc_info_marques_id_seq'::regclass);


--
-- Name: parc_info_mouvements_consommables id; Type: DEFAULT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_mouvements_consommables ALTER COLUMN id SET DEFAULT nextval('public.parc_info_mouvements_consommables_id_seq'::regclass);


--
-- Name: parc_info_types_consommables id; Type: DEFAULT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_types_consommables ALTER COLUMN id SET DEFAULT nextval('public.parc_info_types_consommables_id_seq'::regclass);


--
-- Name: parc_info_types_cpus id; Type: DEFAULT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_types_cpus ALTER COLUMN id SET DEFAULT nextval('public.parc_info_types_cpus_id_seq'::regclass);


--
-- Name: parc_info_types_disques id; Type: DEFAULT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_types_disques ALTER COLUMN id SET DEFAULT nextval('public.parc_info_types_disques_id_seq'::regclass);


--
-- Name: parc_info_types_imprimantes id; Type: DEFAULT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_types_imprimantes ALTER COLUMN id SET DEFAULT nextval('public.parc_info_types_imprimantes_id_seq'::regclass);


--
-- Name: parc_info_types_infrastructures id; Type: DEFAULT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_types_infrastructures ALTER COLUMN id SET DEFAULT nextval('public.parc_info_types_infrastructures_id_seq'::regclass);


--
-- Name: parc_info_types_licences id; Type: DEFAULT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_types_licences ALTER COLUMN id SET DEFAULT nextval('public.parc_info_types_licences_id_seq'::regclass);


--
-- Name: parc_info_types_mobiles id; Type: DEFAULT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_types_mobiles ALTER COLUMN id SET DEFAULT nextval('public.parc_info_types_mobiles_id_seq'::regclass);


--
-- Name: parc_info_types_os id; Type: DEFAULT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_types_os ALTER COLUMN id SET DEFAULT nextval('public.parc_info_types_os_id_seq'::regclass);


--
-- Name: parc_info_types_rams id; Type: DEFAULT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_types_rams ALTER COLUMN id SET DEFAULT nextval('public.parc_info_types_rams_id_seq'::regclass);


--
-- Name: parc_info_types_reseaux id; Type: DEFAULT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_types_reseaux ALTER COLUMN id SET DEFAULT nextval('public.parc_info_types_reseaux_id_seq'::regclass);


--
-- Name: permissions id; Type: DEFAULT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.permissions ALTER COLUMN id SET DEFAULT nextval('public.permissions_id_seq'::regclass);


--
-- Name: roles id; Type: DEFAULT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.roles ALTER COLUMN id SET DEFAULT nextval('public.roles_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.cores_users_id_seq'::regclass);


--
-- Data for Name: activity_log; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.activity_log (id, log_name, description, subject_type, subject_id, causer_type, causer_id, properties, created_at, updated_at, event, batch_uuid, module, context, ip_address, user_agent, causer_roles, expires_at, retention_months) FROM stdin;
1	core	created	Modules\\Core\\Models\\Module	1	\N	\N	{"attributes":{"id":1,"name":"Core","slug":"core","description":"Module principal de Keystone g\\u00e9rant l'authentification, les utilisateurs, les r\\u00f4les, les permissions et la gestion des modules. Il inclura prochainement une table 'configs' pour la gestion centralis\\u00e9e des configurations syst\\u00e8me.","version":"1.0.0","is_active":true,"is_required":false,"dependencies":[],"config":null,"icon":"fas fa-cogs","sort_order":0,"installed_at":"2026-05-21T19:30:12.000000Z","activated_at":null,"created_at":"2026-05-21T19:30:12.000000Z","updated_at":"2026-05-21T19:30:12.000000Z","deleted_at":null}}	2026-05-21 19:30:12	2026-05-21 19:30:12	created	\N	core	{"route":null,"method":"GET","url":"https:\\/\\/parc_info.local"}	127.0.0.1	Symfony	\N	2027-05-21 19:30:12	12
2	core	created	Modules\\Core\\Models\\Module	2	\N	\N	{"attributes":{"id":2,"name":"Grh","slug":"grh","description":"","version":"1.0.0","is_active":true,"is_required":false,"dependencies":[],"config":null,"icon":"fas fa-cube","sort_order":0,"installed_at":"2026-05-21T19:30:12.000000Z","activated_at":null,"created_at":"2026-05-21T19:30:12.000000Z","updated_at":"2026-05-21T19:30:12.000000Z","deleted_at":null}}	2026-05-21 19:30:12	2026-05-21 19:30:12	created	\N	core	{"route":null,"method":"GET","url":"https:\\/\\/parc_info.local"}	127.0.0.1	Symfony	\N	2027-05-21 19:30:12	12
3	core	created	Modules\\Core\\Models\\Module	3	\N	\N	{"attributes":{"id":3,"name":"Organisation","slug":"organisation","description":"","version":"1.0.0","is_active":true,"is_required":false,"dependencies":[],"config":null,"icon":"fas fa-cube","sort_order":0,"installed_at":"2026-05-21T19:30:12.000000Z","activated_at":null,"created_at":"2026-05-21T19:30:12.000000Z","updated_at":"2026-05-21T19:30:12.000000Z","deleted_at":null}}	2026-05-21 19:30:12	2026-05-21 19:30:12	created	\N	core	{"route":null,"method":"GET","url":"https:\\/\\/parc_info.local"}	127.0.0.1	Symfony	\N	2027-05-21 19:30:12	12
4	core	created	Modules\\Core\\Models\\Module	4	\N	\N	{"attributes":{"id":4,"name":"ParcInfo","slug":"parcinfo","description":"","version":"1.0.0","is_active":true,"is_required":false,"dependencies":[],"config":null,"icon":"fas fa-cube","sort_order":0,"installed_at":"2026-05-21T19:30:12.000000Z","activated_at":null,"created_at":"2026-05-21T19:30:12.000000Z","updated_at":"2026-05-21T19:30:12.000000Z","deleted_at":null}}	2026-05-21 19:30:12	2026-05-21 19:30:12	created	\N	core	{"route":null,"method":"GET","url":"https:\\/\\/parc_info.local"}	127.0.0.1	Symfony	\N	2027-05-21 19:30:12	12
5	core	created	Modules\\Core\\Models\\User	3	\N	\N	{"attributes":{"id":3,"name":"ibrahim","last_name":"gninasse","user_name":"igninasse","email":"ibrahim.gninasse@gmail.com","service":null,"email_verified_at":null,"password":"$2y$12$tXPElQfILSGRVcxTYbAuPOhpO7ZUysMVVX8xjqzEq31NicFNnPAAW","remember_token":null,"created_at":"2026-05-21T19:32:54.000000Z","updated_at":"2026-05-21T19:32:54.000000Z","is_active":true,"avatar":null,"dossier_employe_id":null}}	2026-05-21 19:32:54	2026-05-21 19:32:54	created	\N	core	{"route":null,"method":"GET","url":"https:\\/\\/parc_info.local"}	127.0.0.1	Symfony	\N	2027-05-21 19:32:54	12
6	core	updated	Modules\\Core\\Models\\User	3	Modules\\Core\\Models\\User	3	{"attributes":{"remember_token":"E6C2XATGMZGl0uG9VcS9NuhBoC6shypcICj7Qk0BZ5Cp8GwqdFDD8M0HWphY"},"old":{"remember_token":null}}	2026-05-21 19:34:32	2026-05-21 19:34:32	updated	\N	core	{"route":"login.post","method":"POST","url":"http:\\/\\/parc_info.local\\/login"}	127.0.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	["super-admin"]	2027-05-21 19:34:32	12
7	default	created	Modules\\ParcInfo\\Models\\Equipement	1	Modules\\Core\\Models\\User	3	{"attributes":{"id":1,"code_inventaire":"NET-2026-0001","numero_serie":"derf","marque_id":1,"modele":"fg","date_acquisition":null,"date_mise_en_service":null,"valeur_achat":null,"duree_vie_probable":null,"date_fin_garantie":null,"statut":"en_stock","etat":"bon","tags":null,"created_at":"2026-05-21T19:34:59.000000Z","updated_at":"2026-05-21T19:34:59.000000Z"}}	2026-05-21 19:34:59	2026-05-21 19:34:59	created	\N	\N	\N	\N	\N	\N	\N	\N
8	default	updated	Modules\\ParcInfo\\Models\\Equipement	1	Modules\\Core\\Models\\User	3	{"attributes":{"statut":"en_service","updated_at":"2026-05-21T19:39:26.000000Z"},"old":{"statut":"en_stock","updated_at":"2026-05-21T19:34:59.000000Z"}}	2026-05-21 19:39:26	2026-05-21 19:39:26	updated	\N	\N	\N	\N	\N	\N	\N	\N
9	default	updated	Modules\\ParcInfo\\Models\\Equipement	1	Modules\\Core\\Models\\User	3	{"attributes":{"statut":"en_stock","updated_at":"2026-05-21T19:39:37.000000Z"},"old":{"statut":"en_service","updated_at":"2026-05-21T19:39:26.000000Z"}}	2026-05-21 19:39:37	2026-05-21 19:39:37	updated	\N	\N	\N	\N	\N	\N	\N	\N
10	default	created	Modules\\ParcInfo\\Models\\Equipement	2	Modules\\Core\\Models\\User	3	{"attributes":{"id":2,"code_inventaire":"NET-2026-0002","numero_serie":"sdfghjk","marque_id":1,"modele":"fvgb","date_acquisition":null,"date_mise_en_service":null,"valeur_achat":null,"duree_vie_probable":null,"date_fin_garantie":null,"statut":"en_stock","etat":"bon","tags":null,"created_at":"2026-05-21T21:22:34.000000Z","updated_at":"2026-05-21T21:22:34.000000Z"}}	2026-05-21 21:22:34	2026-05-21 21:22:34	created	\N	\N	\N	\N	\N	\N	\N	\N
11	default	created	Modules\\ParcInfo\\Models\\Equipement	3	Modules\\Core\\Models\\User	3	{"attributes":{"id":3,"code_inventaire":"SRV-2026-0003","numero_serie":"dfg-frtg-gh-derf","marque_id":1,"modele":"Optiplex-dfg","date_acquisition":null,"date_mise_en_service":null,"valeur_achat":null,"duree_vie_probable":null,"date_fin_garantie":null,"statut":"en_service","etat":"bon","tags":null,"created_at":"2026-05-22T13:47:46.000000Z","updated_at":"2026-05-22T13:47:46.000000Z"}}	2026-05-22 13:47:46	2026-05-22 13:47:46	created	\N	\N	\N	\N	\N	\N	\N	\N
12	default	created	Modules\\ParcInfo\\Models\\Equipement	4	Modules\\Core\\Models\\User	3	{"attributes":{"id":4,"code_inventaire":"VM-2026-0004","numero_serie":"edr","marque_id":1,"modele":"ffgh","date_acquisition":null,"date_mise_en_service":null,"valeur_achat":null,"duree_vie_probable":null,"date_fin_garantie":null,"statut":"en_service","etat":"bon","tags":null,"created_at":"2026-05-22T13:49:36.000000Z","updated_at":"2026-05-22T13:49:36.000000Z"}}	2026-05-22 13:49:36	2026-05-22 13:49:36	created	\N	\N	\N	\N	\N	\N	\N	\N
13	default	created	Modules\\ParcInfo\\Models\\Equipement	5	Modules\\Core\\Models\\User	3	{"attributes":{"id":5,"code_inventaire":"SRV-2026-0005","numero_serie":"dfg-frtg-gh-der","marque_id":null,"modele":"derf-rfgt","date_acquisition":null,"date_mise_en_service":null,"valeur_achat":null,"duree_vie_probable":null,"date_fin_garantie":null,"statut":"en_stock","etat":"bon","tags":null,"created_at":"2026-05-22T14:48:53.000000Z","updated_at":"2026-05-22T14:48:53.000000Z"}}	2026-05-22 14:48:53	2026-05-22 14:48:53	created	\N	\N	\N	\N	\N	\N	\N	\N
14	default	created	Modules\\ParcInfo\\Models\\Equipement	6	Modules\\Core\\Models\\User	3	{"attributes":{"id":6,"code_inventaire":"INV-2026-0006","numero_serie":"ddfg","marque_id":1,"modele":"fg","date_acquisition":null,"date_mise_en_service":null,"valeur_achat":null,"duree_vie_probable":null,"date_fin_garantie":null,"statut":"en_stock","etat":"bon","tags":null,"created_at":"2026-05-22T17:23:30.000000Z","updated_at":"2026-05-22T17:23:30.000000Z"}}	2026-05-22 17:23:30	2026-05-22 17:23:30	created	\N	\N	\N	\N	\N	\N	\N	\N
32	default	updated	Modules\\ParcInfo\\Models\\Equipement	12	Modules\\Core\\Models\\User	3	{"attributes":{"statut":"en_service","updated_at":"2026-06-03T11:01:08.000000Z"},"old":{"statut":"en_stock","updated_at":"2026-06-03T11:00:16.000000Z"}}	2026-06-03 11:01:08	2026-06-03 11:01:08	updated	\N	\N	\N	\N	\N	\N	\N	\N
15	default	created	Modules\\ParcInfo\\Models\\Equipement	7	Modules\\Core\\Models\\User	3	{"attributes":{"id":7,"code_inventaire":"MOB-2026-0007","numero_serie":"ddfgfgh","marque_id":1,"modele":"dcfv","date_acquisition":null,"date_mise_en_service":null,"valeur_achat":null,"duree_vie_probable":null,"date_fin_garantie":null,"statut":"en_stock","etat":"bon","tags":null,"created_at":"2026-05-22T17:24:22.000000Z","updated_at":"2026-05-22T17:24:22.000000Z"}}	2026-05-22 17:24:22	2026-05-22 17:24:22	created	\N	\N	\N	\N	\N	\N	\N	\N
16	core	created	Modules\\Core\\Models\\Role	2	Modules\\Core\\Models\\User	3	{"attributes":{"id":2,"name":"admin","guard_name":"web","created_at":"2026-05-30T14:23:29.000000Z","updated_at":"2026-05-30T14:23:29.000000Z","description":null}}	2026-05-30 14:23:29	2026-05-30 14:23:29	created	\N	core	{"route":"cores.roles.store","method":"POST","url":"http:\\/\\/parc_info.local\\/cores\\/roles"}	127.0.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	["super-admin"]	2027-05-30 14:23:29	12
17	default	deleted	Modules\\ParcInfo\\Models\\Equipement	5	Modules\\Core\\Models\\User	3	{"old":{"id":5,"code_inventaire":"SRV-2026-0005","numero_serie":"dfg-frtg-gh-der","marque_id":null,"modele":"derf-rfgt","date_acquisition":null,"date_mise_en_service":null,"valeur_achat":null,"duree_vie_probable":null,"date_fin_garantie":null,"statut":"en_stock","etat":"bon","tags":null,"created_at":"2026-05-22T14:48:53.000000Z","updated_at":"2026-05-22T14:48:53.000000Z"}}	2026-06-01 10:35:16	2026-06-01 10:35:16	deleted	\N	\N	\N	\N	\N	\N	\N	\N
18	default	deleted	Modules\\ParcInfo\\Models\\Equipement	6	Modules\\Core\\Models\\User	3	{"old":{"id":6,"code_inventaire":"INV-2026-0006","numero_serie":"ddfg","marque_id":1,"modele":"fg","date_acquisition":null,"date_mise_en_service":null,"valeur_achat":null,"duree_vie_probable":null,"date_fin_garantie":null,"statut":"en_stock","etat":"bon","tags":null,"created_at":"2026-05-22T17:23:30.000000Z","updated_at":"2026-05-22T17:23:30.000000Z"}}	2026-06-01 10:44:25	2026-06-01 10:44:25	deleted	\N	\N	\N	\N	\N	\N	\N	\N
19	default	created	Modules\\ParcInfo\\Models\\Equipement	8	Modules\\Core\\Models\\User	3	{"attributes":{"id":8,"code_inventaire":"SRV-2026-0008","numero_serie":"S\\/N 234-345","marque_id":1,"modele":"PowerEdge","date_acquisition":null,"date_mise_en_service":null,"valeur_achat":null,"duree_vie_probable":null,"date_fin_garantie":null,"statut":"en_stock","etat":"bon","tags":null,"created_at":"2026-06-01T10:47:08.000000Z","updated_at":"2026-06-01T10:47:08.000000Z"}}	2026-06-01 10:47:08	2026-06-01 10:47:08	created	\N	\N	\N	\N	\N	\N	\N	\N
20	default	created	Modules\\ParcInfo\\Models\\Equipement	9	Modules\\Core\\Models\\User	3	{"attributes":{"id":9,"code_inventaire":"SRV-2026-0009","numero_serie":"dfg-frtg-gh-de","marque_id":2,"modele":"PowerEdge3","date_acquisition":null,"date_mise_en_service":null,"valeur_achat":null,"duree_vie_probable":null,"date_fin_garantie":null,"statut":"en_stock","etat":"bon","tags":null,"created_at":"2026-06-01T11:59:59.000000Z","updated_at":"2026-06-01T11:59:59.000000Z"}}	2026-06-01 11:59:59	2026-06-01 11:59:59	created	\N	\N	\N	\N	\N	\N	\N	\N
21	default	updated	Modules\\ParcInfo\\Models\\Equipement	9	Modules\\Core\\Models\\User	3	{"attributes":{"statut":"en_service","updated_at":"2026-06-01T12:13:30.000000Z"},"old":{"statut":"en_stock","updated_at":"2026-06-01T11:59:59.000000Z"}}	2026-06-01 12:13:30	2026-06-01 12:13:30	updated	\N	\N	\N	\N	\N	\N	\N	\N
22	default	updated	Modules\\ParcInfo\\Models\\Equipement	9	Modules\\Core\\Models\\User	3	{"attributes":{"statut":"en_stock","updated_at":"2026-06-01T12:13:51.000000Z"},"old":{"statut":"en_service","updated_at":"2026-06-01T12:13:30.000000Z"}}	2026-06-01 12:13:51	2026-06-01 12:13:51	updated	\N	\N	\N	\N	\N	\N	\N	\N
23	default	updated	Modules\\ParcInfo\\Models\\Equipement	9	Modules\\Core\\Models\\User	3	{"attributes":{"statut":"en_service","updated_at":"2026-06-01T12:15:32.000000Z"},"old":{"statut":"en_stock","updated_at":"2026-06-01T12:13:51.000000Z"}}	2026-06-01 12:15:32	2026-06-01 12:15:32	updated	\N	\N	\N	\N	\N	\N	\N	\N
24	default	updated	Modules\\ParcInfo\\Models\\Equipement	9	Modules\\Core\\Models\\User	3	{"attributes":{"statut":"en_stock","updated_at":"2026-06-01T12:17:25.000000Z"},"old":{"statut":"en_service","updated_at":"2026-06-01T12:15:32.000000Z"}}	2026-06-01 12:17:25	2026-06-01 12:17:25	updated	\N	\N	\N	\N	\N	\N	\N	\N
25	default	updated	Modules\\ParcInfo\\Models\\Equipement	9	Modules\\Core\\Models\\User	3	{"attributes":{"statut":"en_service","updated_at":"2026-06-01T12:35:48.000000Z"},"old":{"statut":"en_stock","updated_at":"2026-06-01T12:17:25.000000Z"}}	2026-06-01 12:35:48	2026-06-01 12:35:48	updated	\N	\N	\N	\N	\N	\N	\N	\N
26	default	updated	Modules\\ParcInfo\\Models\\Equipement	9	Modules\\Core\\Models\\User	3	{"attributes":{"date_acquisition":"2026-06-03T00:00:00.000000Z","updated_at":"2026-06-03T10:14:32.000000Z"},"old":{"date_acquisition":null,"updated_at":"2026-06-01T12:35:48.000000Z"}}	2026-06-03 10:14:32	2026-06-03 10:14:32	updated	\N	\N	\N	\N	\N	\N	\N	\N
27	default	created	Modules\\ParcInfo\\Models\\Equipement	10	Modules\\Core\\Models\\User	3	{"attributes":{"id":10,"code_inventaire":"VM-2026-0010","numero_serie":"dfg-frtg-gh-gtyh","marque_id":null,"modele":"Machine Virtuelle","date_acquisition":null,"date_mise_en_service":null,"valeur_achat":null,"duree_vie_probable":null,"date_fin_garantie":null,"statut":"en_service","etat":"bon","tags":null,"created_at":"2026-06-03T10:35:05.000000Z","updated_at":"2026-06-03T10:35:05.000000Z"}}	2026-06-03 10:35:05	2026-06-03 10:35:05	created	\N	\N	\N	\N	\N	\N	\N	\N
28	default	created	Modules\\ParcInfo\\Models\\Equipement	11	Modules\\Core\\Models\\User	3	{"attributes":{"id":11,"code_inventaire":"MOB-2026-0011","numero_serie":"ddfg-derf-der","marque_id":3,"modele":"derf","date_acquisition":null,"date_mise_en_service":null,"valeur_achat":null,"duree_vie_probable":null,"date_fin_garantie":null,"statut":"en_stock","etat":"bon","tags":null,"created_at":"2026-06-03T10:59:32.000000Z","updated_at":"2026-06-03T10:59:32.000000Z"}}	2026-06-03 10:59:32	2026-06-03 10:59:32	created	\N	\N	\N	\N	\N	\N	\N	\N
29	default	deleted	Modules\\ParcInfo\\Models\\Equipement	7	Modules\\Core\\Models\\User	3	{"old":{"id":7,"code_inventaire":"MOB-2026-0007","numero_serie":"ddfgfgh","marque_id":1,"modele":"dcfv","date_acquisition":null,"date_mise_en_service":null,"valeur_achat":null,"duree_vie_probable":null,"date_fin_garantie":null,"statut":"en_stock","etat":"bon","tags":null,"created_at":"2026-05-22T17:24:22.000000Z","updated_at":"2026-05-22T17:24:22.000000Z"}}	2026-06-03 10:59:42	2026-06-03 10:59:42	deleted	\N	\N	\N	\N	\N	\N	\N	\N
30	default	deleted	Modules\\ParcInfo\\Models\\Equipement	11	Modules\\Core\\Models\\User	3	{"old":{"id":11,"code_inventaire":"MOB-2026-0011","numero_serie":"ddfg-derf-der","marque_id":3,"modele":"derf","date_acquisition":null,"date_mise_en_service":null,"valeur_achat":null,"duree_vie_probable":null,"date_fin_garantie":null,"statut":"en_stock","etat":"bon","tags":null,"created_at":"2026-06-03T10:59:32.000000Z","updated_at":"2026-06-03T10:59:32.000000Z"}}	2026-06-03 10:59:49	2026-06-03 10:59:49	deleted	\N	\N	\N	\N	\N	\N	\N	\N
31	default	created	Modules\\ParcInfo\\Models\\Equipement	12	Modules\\Core\\Models\\User	3	{"attributes":{"id":12,"code_inventaire":"MOB-2026-0011","numero_serie":"seedcvfg","marque_id":3,"modele":"edfrvc","date_acquisition":null,"date_mise_en_service":null,"valeur_achat":null,"duree_vie_probable":null,"date_fin_garantie":null,"statut":"en_stock","etat":"bon","tags":null,"created_at":"2026-06-03T11:00:16.000000Z","updated_at":"2026-06-03T11:00:16.000000Z"}}	2026-06-03 11:00:16	2026-06-03 11:00:16	created	\N	\N	\N	\N	\N	\N	\N	\N
33	default	created	Modules\\ParcInfo\\Models\\Equipement	13	Modules\\Core\\Models\\User	3	{"attributes":{"id":13,"code_inventaire":"MOB-2026-0013","numero_serie":"sedrf-frtg","marque_id":1,"modele":"derf-fred","date_acquisition":null,"date_mise_en_service":null,"valeur_achat":null,"duree_vie_probable":null,"date_fin_garantie":null,"statut":"en_service","etat":"bon","tags":null,"created_at":"2026-06-03T11:03:42.000000Z","updated_at":"2026-06-03T11:03:42.000000Z"}}	2026-06-03 11:03:42	2026-06-03 11:03:42	created	\N	\N	\N	\N	\N	\N	\N	\N
34	default	created	Modules\\ParcInfo\\Models\\Equipement	14	Modules\\Core\\Models\\User	3	{"attributes":{"id":14,"code_inventaire":"NET-2026-0014","numero_serie":"derftg","marque_id":1,"modele":"gtyh","date_acquisition":null,"date_mise_en_service":null,"valeur_achat":null,"duree_vie_probable":null,"date_fin_garantie":null,"statut":"en_service","etat":"bon","tags":null,"created_at":"2026-06-03T11:19:51.000000Z","updated_at":"2026-06-03T11:19:51.000000Z"}}	2026-06-03 11:19:51	2026-06-03 11:19:51	created	\N	\N	\N	\N	\N	\N	\N	\N
35	default	created	Modules\\ParcInfo\\Models\\Equipement	15	Modules\\Core\\Models\\User	3	{"attributes":{"id":15,"code_inventaire":"INV-2026-0015","numero_serie":"dfg-frtg-gh","marque_id":1,"modele":"fgh","date_acquisition":null,"date_mise_en_service":null,"valeur_achat":null,"duree_vie_probable":null,"date_fin_garantie":"2026-06-12T00:00:00.000000Z","statut":"en_stock","etat":"bon","tags":null,"created_at":"2026-06-12T15:39:13.000000Z","updated_at":"2026-06-12T15:39:13.000000Z"}}	2026-06-12 15:39:13	2026-06-12 15:39:13	created	\N	\N	\N	\N	\N	\N	\N	\N
36	default	updated	Modules\\ParcInfo\\Models\\Equipement	15	Modules\\Core\\Models\\User	3	{"attributes":{"statut":"en_service","updated_at":"2026-06-12T15:41:15.000000Z"},"old":{"statut":"en_stock","updated_at":"2026-06-12T15:39:13.000000Z"}}	2026-06-12 15:41:15	2026-06-12 15:41:15	updated	\N	\N	\N	\N	\N	\N	\N	\N
37	default	created	Modules\\ParcInfo\\Models\\Equipement	16	Modules\\Core\\Models\\User	3	{"attributes":{"id":16,"code_inventaire":"VM-2026-0016","numero_serie":"vmdfg","marque_id":null,"modele":"Machine Virtuelle","date_acquisition":null,"date_mise_en_service":null,"valeur_achat":null,"duree_vie_probable":null,"date_fin_garantie":null,"statut":"en_service","etat":"bon","tags":null,"created_at":"2026-06-12T15:42:15.000000Z","updated_at":"2026-06-12T15:42:15.000000Z"}}	2026-06-12 15:42:15	2026-06-12 15:42:15	created	\N	\N	\N	\N	\N	\N	\N	\N
38	default	created	Modules\\ParcInfo\\Models\\Equipement	17	Modules\\Core\\Models\\User	3	{"attributes":{"id":17,"code_inventaire":"INV-2026-0017","numero_serie":"S\\/N3drf@#$%^","marque_id":1,"modele":"Optiplex 4000","date_acquisition":null,"date_mise_en_service":null,"valeur_achat":null,"duree_vie_probable":null,"date_fin_garantie":null,"statut":"en_stock","etat":"bon","tags":null,"created_at":"2026-06-12T16:43:01.000000Z","updated_at":"2026-06-12T16:43:01.000000Z"}}	2026-06-12 16:43:01	2026-06-12 16:43:01	created	\N	\N	\N	\N	\N	\N	\N	\N
39	default	created	Modules\\ParcInfo\\Models\\Equipement	18	Modules\\Core\\Models\\User	3	{"attributes":{"id":18,"code_inventaire":"INV-2026-0018","numero_serie":"S?\\/ndfrtg","marque_id":null,"modele":"dfvgh","date_acquisition":null,"date_mise_en_service":null,"valeur_achat":null,"duree_vie_probable":null,"date_fin_garantie":null,"statut":"en_service","etat":"bon","tags":null,"created_at":"2026-06-12T16:43:40.000000Z","updated_at":"2026-06-12T16:43:40.000000Z"}}	2026-06-12 16:43:40	2026-06-12 16:43:40	created	\N	\N	\N	\N	\N	\N	\N	\N
40	default	created	Modules\\ParcInfo\\Models\\Equipement	19	Modules\\Core\\Models\\User	3	{"attributes":{"id":19,"code_inventaire":"INV-2026-0019","numero_serie":"ddfg-hyuj","marque_id":null,"modele":"hjkloi","date_acquisition":null,"date_mise_en_service":null,"valeur_achat":null,"duree_vie_probable":null,"date_fin_garantie":null,"statut":"en_service","etat":"bon","tags":null,"created_at":"2026-06-12T16:44:16.000000Z","updated_at":"2026-06-12T16:44:16.000000Z"}}	2026-06-12 16:44:16	2026-06-12 16:44:16	created	\N	\N	\N	\N	\N	\N	\N	\N
41	default	created	Modules\\ParcInfo\\Models\\Equipement	20	Modules\\Core\\Models\\User	3	{"attributes":{"id":20,"code_inventaire":"INV-2026-0020","numero_serie":"S\\/N 4G7B9X2","marque_id":4,"modele":"OptiPlex 7000","date_acquisition":null,"date_mise_en_service":null,"valeur_achat":null,"duree_vie_probable":null,"date_fin_garantie":null,"statut":"en_stock","etat":"bon","tags":null,"created_at":"2026-06-12T16:52:19.000000Z","updated_at":"2026-06-12T16:52:19.000000Z"}}	2026-06-12 16:52:19	2026-06-12 16:52:19	created	\N	\N	\N	\N	\N	\N	\N	\N
42	default	deleted	Modules\\ParcInfo\\Models\\Equipement	15	Modules\\Core\\Models\\User	3	{"old":{"id":15,"code_inventaire":"INV-2026-0015","numero_serie":"dfg-frtg-gh","marque_id":1,"modele":"fgh","date_acquisition":null,"date_mise_en_service":null,"valeur_achat":null,"duree_vie_probable":null,"date_fin_garantie":"2026-06-12T00:00:00.000000Z","statut":"en_service","etat":"bon","tags":null,"created_at":"2026-06-12T15:39:13.000000Z","updated_at":"2026-06-12T15:41:15.000000Z"}}	2026-06-12 16:54:21	2026-06-12 16:54:21	deleted	\N	\N	\N	\N	\N	\N	\N	\N
43	default	created	Modules\\ParcInfo\\Models\\Equipement	21	Modules\\Core\\Models\\User	3	{"attributes":{"id":21,"code_inventaire":"MOB-2026-0021","numero_serie":"S\\/N R58M30XYZ12","marque_id":5,"modele":"Galaxy A54","date_acquisition":null,"date_mise_en_service":null,"valeur_achat":null,"duree_vie_probable":null,"date_fin_garantie":null,"statut":"en_stock","etat":"bon","tags":null,"created_at":"2026-06-12T17:00:25.000000Z","updated_at":"2026-06-12T17:00:25.000000Z"}}	2026-06-12 17:00:25	2026-06-12 17:00:25	created	\N	\N	\N	\N	\N	\N	\N	\N
44	default	updated	Modules\\ParcInfo\\Models\\Equipement	21	Modules\\Core\\Models\\User	3	{"attributes":{"statut":"en_service","updated_at":"2026-06-12T17:01:21.000000Z"},"old":{"statut":"en_stock","updated_at":"2026-06-12T17:00:25.000000Z"}}	2026-06-12 17:01:21	2026-06-12 17:01:21	updated	\N	\N	\N	\N	\N	\N	\N	\N
45	fournisseur	Création de fournisseur	Modules\\ParcInfo\\Models\\Fournisseur	2	Modules\\Core\\Models\\User	3	[]	2026-06-12 18:26:03	2026-06-12 18:26:03	\N	\N	\N	\N	\N	\N	\N	\N	\N
46	fournisseur	Mise à jour de fournisseur	Modules\\ParcInfo\\Models\\Fournisseur	2	Modules\\Core\\Models\\User	3	[]	2026-06-12 18:26:22	2026-06-12 18:26:22	\N	\N	\N	\N	\N	\N	\N	\N	\N
47	core	created	Modules\\Core\\Models\\User	13	\N	\N	{"attributes":{"id":13,"name":"Test","last_name":"User","user_name":"testuser","email":"testuser6a2c620bdb96a@example.com","service":null,"email_verified_at":null,"password":"$2y$04$IvbN\\/xmnKpqBMj\\/.7js5IOiqLBDWCaxXOEoU5owOMqC1BYSrqXXGG","remember_token":null,"created_at":"2026-06-12T19:46:19.000000Z","updated_at":"2026-06-12T19:46:19.000000Z","is_active":true,"avatar":null,"dossier_employe_id":null}}	2026-06-12 19:46:19	2026-06-12 19:46:19	created	\N	core	{"route":null,"method":"GET","url":"https:\\/\\/parc_info.local"}	127.0.0.1	Symfony	\N	2027-06-12 19:46:19	12
48	core	created	Modules\\Core\\Models\\User	14	\N	\N	{"attributes":{"id":14,"name":"Test","last_name":"User","user_name":"testuser","email":"testuser6a2c620c079cc@example.com","service":null,"email_verified_at":null,"password":"$2y$04$cTsoNQpU\\/TFTsuC7stTpZ.gzyZM7L0ssbTpfZTaOVeiDUZ\\/WOo6y.","remember_token":null,"created_at":"2026-06-12T19:46:20.000000Z","updated_at":"2026-06-12T19:46:20.000000Z","is_active":true,"avatar":null,"dossier_employe_id":null}}	2026-06-12 19:46:20	2026-06-12 19:46:20	created	\N	core	{"route":null,"method":"GET","url":"https:\\/\\/parc_info.local"}	127.0.0.1	Symfony	\N	2027-06-12 19:46:20	12
49	core	created	Modules\\Core\\Models\\User	15	\N	\N	{"attributes":{"id":15,"name":"Test","last_name":"User","user_name":"testuser","email":"testuser6a2c620c22d07@example.com","service":null,"email_verified_at":null,"password":"$2y$04$QqGeI4JD6eszqrrx0T8AZuHodEuhi89KSlxBJMVor8.h8hKz8IXwi","remember_token":null,"created_at":"2026-06-12T19:46:20.000000Z","updated_at":"2026-06-12T19:46:20.000000Z","is_active":true,"avatar":null,"dossier_employe_id":null}}	2026-06-12 19:46:20	2026-06-12 19:46:20	created	\N	core	{"route":null,"method":"GET","url":"https:\\/\\/parc_info.local"}	127.0.0.1	Symfony	\N	2027-06-12 19:46:20	12
50	core	created	Modules\\Core\\Models\\User	16	\N	\N	{"attributes":{"id":16,"name":"Test","last_name":"User","user_name":"testuser","email":"testuser6a2c620c4dc51@example.com","service":null,"email_verified_at":null,"password":"$2y$04$o9f3z4iUlmDqWZ1yoTIcwuJSSDOgg2WvIESozMUQdCd\\/5QmpTnKya","remember_token":null,"created_at":"2026-06-12T19:46:20.000000Z","updated_at":"2026-06-12T19:46:20.000000Z","is_active":true,"avatar":null,"dossier_employe_id":null}}	2026-06-12 19:46:20	2026-06-12 19:46:20	created	\N	core	{"route":null,"method":"GET","url":"https:\\/\\/parc_info.local"}	127.0.0.1	Symfony	\N	2027-06-12 19:46:20	12
51	core	created	Modules\\Core\\Models\\User	17	\N	\N	{"attributes":{"id":17,"name":"Test","last_name":"User","user_name":"testuser","email":"testuser6a2c620c6fc99@example.com","service":null,"email_verified_at":null,"password":"$2y$04$jatRjzAmwXeuADbsxsqqMe02cuiZiRpsgb8ZbOi2yuEMiIJbMrLuW","remember_token":null,"created_at":"2026-06-12T19:46:20.000000Z","updated_at":"2026-06-12T19:46:20.000000Z","is_active":true,"avatar":null,"dossier_employe_id":null}}	2026-06-12 19:46:20	2026-06-12 19:46:20	created	\N	core	{"route":null,"method":"GET","url":"https:\\/\\/parc_info.local"}	127.0.0.1	Symfony	\N	2027-06-12 19:46:20	12
52	core	created	Modules\\Core\\Models\\User	18	\N	\N	{"attributes":{"id":18,"name":"Test","last_name":"User","user_name":"testuser","email":"testuser6a2c620c949a5@example.com","service":null,"email_verified_at":null,"password":"$2y$04$bSEmlu51nYdGaEnmVR.2peql37Dm2swR5ly0.cVlUUCUSAzgvrip.","remember_token":null,"created_at":"2026-06-12T19:46:20.000000Z","updated_at":"2026-06-12T19:46:20.000000Z","is_active":true,"avatar":null,"dossier_employe_id":null}}	2026-06-12 19:46:20	2026-06-12 19:46:20	created	\N	core	{"route":null,"method":"GET","url":"https:\\/\\/parc_info.local"}	127.0.0.1	Symfony	\N	2027-06-12 19:46:20	12
53	core	created	Modules\\Core\\Models\\User	19	\N	\N	{"attributes":{"id":19,"name":"Test","last_name":"User","user_name":"testuser","email":"testuser6a2c620cbebba@example.com","service":null,"email_verified_at":null,"password":"$2y$04$o1aw0gatquJREQ8GX7QyJugExOYBwP2vdv2Jq0NF9owss03aB0B3a","remember_token":null,"created_at":"2026-06-12T19:46:20.000000Z","updated_at":"2026-06-12T19:46:20.000000Z","is_active":true,"avatar":null,"dossier_employe_id":null}}	2026-06-12 19:46:20	2026-06-12 19:46:20	created	\N	core	{"route":null,"method":"GET","url":"https:\\/\\/parc_info.local"}	127.0.0.1	Symfony	\N	2027-06-12 19:46:20	12
54	core	created	Modules\\Core\\Models\\User	20	\N	\N	{"attributes":{"id":20,"name":"Test","last_name":"User","user_name":"testuser","email":"testuser6a2c620cd80af@example.com","service":null,"email_verified_at":null,"password":"$2y$04$5l9JVeZETJb7kviVPIH6heCyuOKI9wm5wX2iBY9srxwINsROT4aYS","remember_token":null,"created_at":"2026-06-12T19:46:20.000000Z","updated_at":"2026-06-12T19:46:20.000000Z","is_active":true,"avatar":null,"dossier_employe_id":null}}	2026-06-12 19:46:20	2026-06-12 19:46:20	created	\N	core	{"route":null,"method":"GET","url":"https:\\/\\/parc_info.local"}	127.0.0.1	Symfony	\N	2027-06-12 19:46:20	12
55	core	created	Modules\\Core\\Models\\User	21	\N	\N	{"attributes":{"id":21,"name":"Test","last_name":"User","user_name":"testuser","email":"testuser6a2c620cf0f0b@example.com","service":null,"email_verified_at":null,"password":"$2y$04$hqsxQpCUSOb.i2sl0nSuoudh1ThTRvMhaf2HeSKBa51zTpxySge3K","remember_token":null,"created_at":"2026-06-12T19:46:20.000000Z","updated_at":"2026-06-12T19:46:20.000000Z","is_active":true,"avatar":null,"dossier_employe_id":null}}	2026-06-12 19:46:20	2026-06-12 19:46:20	created	\N	core	{"route":null,"method":"GET","url":"https:\\/\\/parc_info.local"}	127.0.0.1	Symfony	\N	2027-06-12 19:46:20	12
56	logiciel	Création de logiciel	Modules\\ParcInfo\\Models\\Logiciel	2	Modules\\Core\\Models\\User	3	[]	2026-06-12 21:26:36	2026-06-12 21:26:36	\N	\N	\N	\N	\N	\N	\N	\N	\N
57	fournisseur	Ajout d'un contact pour le fournisseur	Modules\\ParcInfo\\Models\\Fournisseur	2	Modules\\Core\\Models\\User	3	[]	2026-06-12 21:42:38	2026-06-12 21:42:38	\N	\N	\N	\N	\N	\N	\N	\N	\N
58	fournisseur	Mise à jour de fournisseur	Modules\\ParcInfo\\Models\\Fournisseur	2	Modules\\Core\\Models\\User	3	[]	2026-06-12 21:46:00	2026-06-12 21:46:00	\N	\N	\N	\N	\N	\N	\N	\N	\N
59	fournisseur	Mise à jour de fournisseur	Modules\\ParcInfo\\Models\\Fournisseur	2	Modules\\Core\\Models\\User	3	[]	2026-06-12 21:48:08	2026-06-12 21:48:08	\N	\N	\N	\N	\N	\N	\N	\N	\N
60	licence	Création de licence	Modules\\ParcInfo\\Models\\Licence	1	Modules\\Core\\Models\\User	3	[]	2026-06-12 22:07:37	2026-06-12 22:07:37	\N	\N	\N	\N	\N	\N	\N	\N	\N
61	licence	Mise à jour de licence	Modules\\ParcInfo\\Models\\Licence	1	Modules\\Core\\Models\\User	3	[]	2026-06-12 22:20:53	2026-06-12 22:20:53	\N	\N	\N	\N	\N	\N	\N	\N	\N
62	consommable	Ajout au catalogue	Modules\\ParcInfo\\Models\\Consommable	1	Modules\\Core\\Models\\User	3	[]	2026-06-12 22:38:21	2026-06-12 22:38:21	\N	\N	\N	\N	\N	\N	\N	\N	\N
63	default	deleted	Modules\\ParcInfo\\Models\\Equipement	19	Modules\\Core\\Models\\User	3	{"old":{"id":19,"code_inventaire":"INV-2026-0019","numero_serie":"ddfg-hyuj","marque_id":null,"modele":"hjkloi","date_acquisition":null,"date_mise_en_service":null,"valeur_achat":null,"duree_vie_probable":null,"date_fin_garantie":null,"statut":"en_service","etat":"bon","tags":null,"created_at":"2026-06-12T16:44:16.000000Z","updated_at":"2026-06-12T16:44:16.000000Z","ref_bordereau":null,"direction_id":null,"service_id":null,"unite_id":null}}	2026-06-16 08:48:01	2026-06-16 08:48:01	deleted	\N	\N	\N	\N	\N	\N	\N	\N
64	default	deleted	Modules\\ParcInfo\\Models\\Equipement	18	Modules\\Core\\Models\\User	3	{"old":{"id":18,"code_inventaire":"INV-2026-0018","numero_serie":"S?\\/ndfrtg","marque_id":null,"modele":"dfvgh","date_acquisition":null,"date_mise_en_service":null,"valeur_achat":null,"duree_vie_probable":null,"date_fin_garantie":null,"statut":"en_service","etat":"bon","tags":null,"created_at":"2026-06-12T16:43:40.000000Z","updated_at":"2026-06-12T16:43:40.000000Z","ref_bordereau":null,"direction_id":null,"service_id":null,"unite_id":null}}	2026-06-16 08:48:10	2026-06-16 08:48:10	deleted	\N	\N	\N	\N	\N	\N	\N	\N
65	core	created	Modules\\Core\\Models\\Role	3	Modules\\Core\\Models\\User	3	{"attributes":{"id":3,"name":"Gestionnaire","guard_name":"web","created_at":"2026-06-16T09:36:55.000000Z","updated_at":"2026-06-16T09:36:55.000000Z","description":null}}	2026-06-16 09:36:55	2026-06-16 09:36:55	created	\N	core	{"route":"cores.roles.store","method":"POST","url":"http:\\/\\/parc_info.local\\/cores\\/roles"}	127.0.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	["super-admin"]	2027-06-16 09:36:55	12
66	core	created	Modules\\Core\\Models\\User	22	Modules\\Core\\Models\\User	3	{"attributes":{"id":22,"name":"Elise","last_name":"TOE","user_name":"elise","email":"elise@gmail.com","service":"Direction des Services Informatiques","email_verified_at":null,"password":"$2y$12$8SDprbrU.J7QZgB5d5cjoOFCmlCSkn47.69.Mwyu0HK1tjqmJm2Y2","remember_token":null,"created_at":"2026-06-16T09:44:59.000000Z","updated_at":"2026-06-16T09:44:59.000000Z","is_active":true,"avatar":null,"dossier_employe_id":null}}	2026-06-16 09:44:59	2026-06-16 09:44:59	created	\N	core	{"route":"cores.users.store","method":"POST","url":"http:\\/\\/parc_info.local\\/cores\\/users"}	127.0.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	["super-admin"]	2027-06-16 09:44:59	12
67	roles	role_assigned	Modules\\Core\\Models\\User	22	Modules\\Core\\Models\\User	3	{"role":"super-admin","action":"assigned"}	2026-06-16 09:45:40	2026-06-16 09:45:40	\N	\N	core	{"route":"cores.users.assign-role","method":"POST","url":"http:\\/\\/parc_info.local\\/cores\\/users\\/22\\/roles"}	127.0.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	["super-admin"]	2027-06-16 09:45:40	12
68	roles	role_assigned	Modules\\Core\\Models\\User	22	Modules\\Core\\Models\\User	3	{"role":"admin","action":"assigned"}	2026-06-16 09:45:50	2026-06-16 09:45:50	\N	\N	core	{"route":"cores.users.assign-role","method":"POST","url":"http:\\/\\/parc_info.local\\/cores\\/users\\/22\\/roles"}	127.0.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	["super-admin"]	2027-06-16 09:45:50	12
69	permissions	permission_given	Modules\\Core\\Models\\User	22	Modules\\Core\\Models\\User	3	{"permission":"[{\\"id\\":138,\\"name\\":\\"parc-info.referentiels.types-imprimantes.destroy\\",\\"guard_name\\":\\"web\\",\\"created_at\\":\\"2026-06-12T19:38:29.000000Z\\",\\"updated_at\\":\\"2026-06-12T19:38:29.000000Z\\",\\"label\\":\\"R\\\\u00e9f\\\\u00e9rentiel - Supprimer un type d'imprimante\\",\\"module\\":\\"parcinfo\\",\\"category\\":\\"delete\\",\\"description\\":\\"R\\\\u00e9f\\\\u00e9rentiel - Supprimer un type d'imprimante\\",\\"group\\":null,\\"sort_order\\":0,\\"is_visible\\":true}]","action":"given"}	2026-06-16 09:47:11	2026-06-16 09:47:11	\N	\N	core	{"route":"cores.users.assign-permissions","method":"POST","url":"http:\\/\\/parc_info.local\\/cores\\/users\\/22\\/permissions"}	127.0.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	["super-admin"]	2027-06-16 09:47:11	12
70	default	updated	Modules\\ParcInfo\\Models\\Equipement	20	Modules\\Core\\Models\\User	3	{"attributes":{"updated_at":"2026-06-16T10:10:27.000000Z","direction_id":13},"old":{"updated_at":"2026-06-12T16:52:19.000000Z","direction_id":null}}	2026-06-16 10:10:27	2026-06-16 10:10:27	updated	\N	\N	\N	\N	\N	\N	\N	\N
71	default	updated	Modules\\ParcInfo\\Models\\Equipement	20	Modules\\Core\\Models\\User	3	{"attributes":{"statut":"en_service","updated_at":"2026-06-16T10:10:27.000000Z","direction_id":13},"old":{"statut":"en_stock","updated_at":"2026-06-12T16:52:19.000000Z","direction_id":null}}	2026-06-16 10:10:27	2026-06-16 10:10:27	updated	\N	\N	\N	\N	\N	\N	\N	\N
72	default	updated	Modules\\ParcInfo\\Models\\Equipement	20	Modules\\Core\\Models\\User	3	{"attributes":{"statut":"en_stock","updated_at":"2026-06-16T10:11:07.000000Z","direction_id":null},"old":{"statut":"en_service","updated_at":"2026-06-16T10:10:27.000000Z","direction_id":13}}	2026-06-16 10:11:07	2026-06-16 10:11:07	updated	\N	\N	\N	\N	\N	\N	\N	\N
73	default	updated	Modules\\ParcInfo\\Models\\Equipement	20	Modules\\Core\\Models\\User	3	{"attributes":{"updated_at":"2026-06-16T10:11:57.000000Z","direction_id":11,"service_id":19},"old":{"updated_at":"2026-06-16T10:11:07.000000Z","direction_id":null,"service_id":null}}	2026-06-16 10:11:57	2026-06-16 10:11:57	updated	\N	\N	\N	\N	\N	\N	\N	\N
74	default	updated	Modules\\ParcInfo\\Models\\Equipement	20	Modules\\Core\\Models\\User	3	{"attributes":{"statut":"en_service","updated_at":"2026-06-16T10:11:57.000000Z","direction_id":11,"service_id":19},"old":{"statut":"en_stock","updated_at":"2026-06-16T10:11:07.000000Z","direction_id":null,"service_id":null}}	2026-06-16 10:11:57	2026-06-16 10:11:57	updated	\N	\N	\N	\N	\N	\N	\N	\N
75	default	updated	Modules\\ParcInfo\\Models\\Equipement	20	Modules\\Core\\Models\\User	3	{"attributes":{"statut":"en_stock","updated_at":"2026-06-16T10:29:30.000000Z","direction_id":null,"service_id":null},"old":{"statut":"en_service","updated_at":"2026-06-16T10:11:57.000000Z","direction_id":11,"service_id":19}}	2026-06-16 10:29:30	2026-06-16 10:29:30	updated	\N	\N	\N	\N	\N	\N	\N	\N
76	default	updated	Modules\\ParcInfo\\Models\\Equipement	20	Modules\\Core\\Models\\User	3	{"attributes":{"updated_at":"2026-06-16T10:30:59.000000Z","direction_id":11,"service_id":19},"old":{"updated_at":"2026-06-16T10:29:30.000000Z","direction_id":null,"service_id":null}}	2026-06-16 10:30:59	2026-06-16 10:30:59	updated	\N	\N	\N	\N	\N	\N	\N	\N
77	default	updated	Modules\\ParcInfo\\Models\\Equipement	20	Modules\\Core\\Models\\User	3	{"attributes":{"statut":"en_service","updated_at":"2026-06-16T10:30:59.000000Z","direction_id":11,"service_id":19},"old":{"statut":"en_stock","updated_at":"2026-06-16T10:29:30.000000Z","direction_id":null,"service_id":null}}	2026-06-16 10:30:59	2026-06-16 10:30:59	updated	\N	\N	\N	\N	\N	\N	\N	\N
78	default	updated	Modules\\ParcInfo\\Models\\Equipement	20	Modules\\Core\\Models\\User	3	{"attributes":{"statut":"en_reparation","updated_at":"2026-06-16T10:33:40.000000Z"},"old":{"statut":"en_service","updated_at":"2026-06-16T10:30:59.000000Z"}}	2026-06-16 10:33:40	2026-06-16 10:33:40	updated	\N	\N	\N	\N	\N	\N	\N	\N
79	default	updated	Modules\\ParcInfo\\Models\\Equipement	20	Modules\\Core\\Models\\User	3	{"attributes":{"statut":"en_service","updated_at":"2026-06-16T10:34:09.000000Z"},"old":{"statut":"en_reparation","updated_at":"2026-06-16T10:33:40.000000Z"}}	2026-06-16 10:34:09	2026-06-16 10:34:09	updated	\N	\N	\N	\N	\N	\N	\N	\N
80	licence	Désaffectation de licence	Modules\\ParcInfo\\Models\\Licence	1	Modules\\Core\\Models\\User	3	[]	2026-06-19 17:53:45	2026-06-19 17:53:45	\N	\N	\N	\N	\N	\N	\N	\N	\N
\.


--
-- Data for Name: cache; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.cache (key, value, expiration) FROM stdin;
laravel-cache-spatie.permission.cache	a:3:{s:5:"alias";a:11:{s:1:"a";s:2:"id";s:1:"b";s:4:"name";s:1:"c";s:10:"guard_name";s:1:"f";s:5:"label";s:1:"g";s:6:"module";s:1:"h";s:8:"category";s:11:"description";s:11:"description";s:5:"group";s:5:"group";s:10:"sort_order";s:10:"sort_order";s:10:"is_visible";s:10:"is_visible";s:1:"r";s:5:"roles";}s:11:"permissions";a:164:{i:0;a:11:{s:1:"a";i:1;s:1:"b";s:20:"cores.dashboard.view";s:1:"c";s:3:"web";s:1:"f";s:23:"Voir le tableau de bord";s:1:"g";s:4:"core";s:1:"h";s:4:"view";s:11:"description";s:23:"Voir le tableau de bord";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:2:{i:0;i:1;i:1;i:2;}}i:1;a:11:{s:1:"a";i:2;s:1:"b";s:17:"cores.users.index";s:1:"c";s:3:"web";s:1:"f";s:30:"Voir la liste des utilisateurs";s:1:"g";s:4:"core";s:1:"h";s:4:"view";s:11:"description";s:30:"Voir la liste des utilisateurs";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:2:{i:0;i:1;i:1;i:2;}}i:2;a:11:{s:1:"a";i:3;s:1:"b";s:17:"cores.users.store";s:1:"c";s:3:"web";s:1:"f";s:21:"Créer un utilisateur";s:1:"g";s:4:"core";s:1:"h";s:6:"create";s:11:"description";s:21:"Créer un utilisateur";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:2:{i:0;i:1;i:1;i:2;}}i:3;a:11:{s:1:"a";i:4;s:1:"b";s:18:"cores.users.update";s:1:"c";s:3:"web";s:1:"f";s:23:"Modifier un utilisateur";s:1:"g";s:4:"core";s:1:"h";s:4:"edit";s:11:"description";s:23:"Modifier un utilisateur";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:2:{i:0;i:1;i:1;i:2;}}i:4;a:11:{s:1:"a";i:5;s:1:"b";s:19:"cores.users.destroy";s:1:"c";s:3:"web";s:1:"f";s:24:"Supprimer un utilisateur";s:1:"g";s:4:"core";s:1:"h";s:6:"delete";s:11:"description";s:24:"Supprimer un utilisateur";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:5;a:11:{s:1:"a";i:6;s:1:"b";s:26:"cores.users.reset-password";s:1:"c";s:3:"web";s:1:"f";s:30:"Réinitialiser le mot de passe";s:1:"g";s:4:"core";s:1:"h";s:5:"other";s:11:"description";s:30:"Réinitialiser le mot de passe";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:6;a:11:{s:1:"a";i:7;s:1:"b";s:25:"cores.users.toggle-status";s:1:"c";s:3:"web";s:1:"f";s:34:"Activer/Désactiver un utilisateur";s:1:"g";s:4:"core";s:1:"h";s:6:"toggle";s:11:"description";s:34:"Activer/Désactiver un utilisateur";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:7;a:11:{s:1:"a";i:8;s:1:"b";s:17:"cores.roles.index";s:1:"c";s:3:"web";s:1:"f";s:24:"Voir la liste des rôles";s:1:"g";s:4:"core";s:1:"h";s:4:"view";s:11:"description";s:24:"Voir la liste des rôles";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:8;a:11:{s:1:"a";i:9;s:1:"b";s:17:"cores.roles.store";s:1:"c";s:3:"web";s:1:"f";s:15:"Créer un rôle";s:1:"g";s:4:"core";s:1:"h";s:6:"create";s:11:"description";s:15:"Créer un rôle";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:9;a:11:{s:1:"a";i:10;s:1:"b";s:18:"cores.roles.update";s:1:"c";s:3:"web";s:1:"f";s:17:"Modifier un rôle";s:1:"g";s:4:"core";s:1:"h";s:4:"edit";s:11:"description";s:17:"Modifier un rôle";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:10;a:11:{s:1:"a";i:11;s:1:"b";s:19:"cores.roles.destroy";s:1:"c";s:3:"web";s:1:"f";s:18:"Supprimer un rôle";s:1:"g";s:4:"core";s:1:"h";s:6:"delete";s:11:"description";s:18:"Supprimer un rôle";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:11;a:11:{s:1:"a";i:12;s:1:"b";s:23:"cores.permissions.index";s:1:"c";s:3:"web";s:1:"f";s:31:"Voir la matrice des permissions";s:1:"g";s:4:"core";s:1:"h";s:4:"view";s:11:"description";s:31:"Voir la matrice des permissions";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:12;a:11:{s:1:"a";i:13;s:1:"b";s:24:"cores.permissions.toggle";s:1:"c";s:3:"web";s:1:"f";s:24:"Modifier les permissions";s:1:"g";s:4:"core";s:1:"h";s:6:"toggle";s:11:"description";s:24:"Modifier les permissions";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:13;a:11:{s:1:"a";i:14;s:1:"b";s:22:"cores.permissions.sync";s:1:"c";s:3:"web";s:1:"f";s:28:"Synchroniser les permissions";s:1:"g";s:4:"core";s:1:"h";s:5:"other";s:11:"description";s:28:"Synchroniser les permissions";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:14;a:11:{s:1:"a";i:15;s:1:"b";s:19:"cores.modules.index";s:1:"c";s:3:"web";s:1:"f";s:25:"Voir la liste des modules";s:1:"g";s:4:"core";s:1:"h";s:4:"view";s:11:"description";s:25:"Voir la liste des modules";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:15;a:11:{s:1:"a";i:16;s:1:"b";s:18:"cores.modules.show";s:1:"c";s:3:"web";s:1:"f";s:29:"Voir les détails d'un module";s:1:"g";s:4:"core";s:1:"h";s:4:"view";s:11:"description";s:29:"Voir les détails d'un module";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:16;a:11:{s:1:"a";i:17;s:1:"b";s:21:"cores.modules.install";s:1:"c";s:3:"web";s:1:"f";s:19:"Installer un module";s:1:"g";s:4:"core";s:1:"h";s:7:"install";s:11:"description";s:19:"Installer un module";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:17;a:11:{s:1:"a";i:18;s:1:"b";s:23:"cores.modules.uninstall";s:1:"c";s:3:"web";s:1:"f";s:23:"Désinstaller un module";s:1:"g";s:4:"core";s:1:"h";s:9:"uninstall";s:11:"description";s:23:"Désinstaller un module";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:18;a:11:{s:1:"a";i:19;s:1:"b";s:20:"cores.modules.enable";s:1:"c";s:3:"web";s:1:"f";s:17:"Activer un module";s:1:"g";s:4:"core";s:1:"h";s:6:"enable";s:11:"description";s:17:"Activer un module";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:19;a:11:{s:1:"a";i:20;s:1:"b";s:21:"cores.modules.disable";s:1:"c";s:3:"web";s:1:"f";s:21:"Désactiver un module";s:1:"g";s:4:"core";s:1:"h";s:7:"disable";s:11:"description";s:21:"Désactiver un module";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:20;a:11:{s:1:"a";i:21;s:1:"b";s:23:"cores.modules.configure";s:1:"c";s:3:"web";s:1:"f";s:20:"Configurer un module";s:1:"g";s:4:"core";s:1:"h";s:9:"configure";s:11:"description";s:20:"Configurer un module";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:21;a:11:{s:1:"a";i:22;s:1:"b";s:22:"cores.activities.index";s:1:"c";s:3:"web";s:1:"f";s:30:"Voir le journal des activités";s:1:"g";s:4:"core";s:1:"h";s:4:"view";s:11:"description";s:30:"Voir le journal des activités";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:22;a:11:{s:1:"a";i:23;s:1:"b";s:21:"cores.activities.data";s:1:"c";s:3:"web";s:1:"f";s:36:"Accéder aux données des activités";s:1:"g";s:4:"core";s:1:"h";s:5:"other";s:11:"description";s:36:"Accéder aux données des activités";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:23;a:11:{s:1:"a";i:24;s:1:"b";s:21:"cores.activities.show";s:1:"c";s:3:"web";s:1:"f";s:33:"Voir les détails d'une activité";s:1:"g";s:4:"core";s:1:"h";s:4:"view";s:11:"description";s:33:"Voir les détails d'une activité";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:24;a:11:{s:1:"a";i:25;s:1:"b";s:23:"cores.activities.export";s:1:"c";s:3:"web";s:1:"f";s:21:"Exporter l'historique";s:1:"g";s:4:"core";s:1:"h";s:5:"other";s:11:"description";s:21:"Exporter l'historique";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:25;a:11:{s:1:"a";i:26;s:1:"b";s:24:"cores.activities.cleanup";s:1:"c";s:3:"web";s:1:"f";s:33:"Nettoyer les anciennes activités";s:1:"g";s:4:"core";s:1:"h";s:5:"other";s:11:"description";s:33:"Nettoyer les anciennes activités";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:26;a:11:{s:1:"a";i:27;s:1:"b";s:18:"grh.dashboard.view";s:1:"c";s:3:"web";s:1:"f";s:27:"Voir le tableau de bord GRH";s:1:"g";s:3:"grh";s:1:"h";s:4:"view";s:11:"description";s:27:"Voir le tableau de bord GRH";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:27;a:11:{s:1:"a";i:28;s:1:"b";s:18:"grh.employes.index";s:1:"c";s:3:"web";s:1:"f";s:36:"Voir la liste des dossiers employés";s:1:"g";s:3:"grh";s:1:"h";s:4:"view";s:11:"description";s:36:"Voir la liste des dossiers employés";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:28;a:11:{s:1:"a";i:29;s:1:"b";s:18:"grh.employes.store";s:1:"c";s:3:"web";s:1:"f";s:26:"Créer un dossier employé";s:1:"g";s:3:"grh";s:1:"h";s:6:"create";s:11:"description";s:26:"Créer un dossier employé";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:29;a:11:{s:1:"a";i:30;s:1:"b";s:19:"grh.employes.update";s:1:"c";s:3:"web";s:1:"f";s:28:"Modifier un dossier employé";s:1:"g";s:3:"grh";s:1:"h";s:4:"edit";s:11:"description";s:28:"Modifier un dossier employé";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:30;a:11:{s:1:"a";i:31;s:1:"b";s:20:"grh.employes.destroy";s:1:"c";s:3:"web";s:1:"f";s:29:"Supprimer un dossier employé";s:1:"g";s:3:"grh";s:1:"h";s:6:"delete";s:11:"description";s:29:"Supprimer un dossier employé";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:31;a:11:{s:1:"a";i:32;s:1:"b";s:17:"grh.employes.show";s:1:"c";s:3:"web";s:1:"f";s:39:"Voir les détails d'un dossier employé";s:1:"g";s:3:"grh";s:1:"h";s:4:"view";s:11:"description";s:39:"Voir les détails d'un dossier employé";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:32;a:11:{s:1:"a";i:33;s:1:"b";s:19:"grh.employes.export";s:1:"c";s:3:"web";s:1:"f";s:31:"Exporter les dossiers employés";s:1:"g";s:3:"grh";s:1:"h";s:5:"other";s:11:"description";s:31:"Exporter les dossiers employés";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:33;a:11:{s:1:"a";i:34;s:1:"b";s:24:"organisation.sites.index";s:1:"c";s:3:"web";s:1:"f";s:23:"Voir la liste des sites";s:1:"g";s:12:"organisation";s:1:"h";s:4:"view";s:11:"description";s:23:"Voir la liste des sites";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:34;a:11:{s:1:"a";i:35;s:1:"b";s:24:"organisation.sites.store";s:1:"c";s:3:"web";s:1:"f";s:14:"Créer un site";s:1:"g";s:12:"organisation";s:1:"h";s:6:"create";s:11:"description";s:14:"Créer un site";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:35;a:11:{s:1:"a";i:36;s:1:"b";s:25:"organisation.sites.update";s:1:"c";s:3:"web";s:1:"f";s:16:"Modifier un site";s:1:"g";s:12:"organisation";s:1:"h";s:4:"edit";s:11:"description";s:16:"Modifier un site";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:36;a:11:{s:1:"a";i:37;s:1:"b";s:26:"organisation.sites.destroy";s:1:"c";s:3:"web";s:1:"f";s:17:"Supprimer un site";s:1:"g";s:12:"organisation";s:1:"h";s:6:"delete";s:11:"description";s:17:"Supprimer un site";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:37;a:11:{s:1:"a";i:38;s:1:"b";s:32:"organisation.sites.toggle-status";s:1:"c";s:3:"web";s:1:"f";s:27:"Activer/Désactiver un site";s:1:"g";s:12:"organisation";s:1:"h";s:6:"toggle";s:11:"description";s:27:"Activer/Désactiver un site";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:38;a:11:{s:1:"a";i:39;s:1:"b";s:29:"organisation.directions.index";s:1:"c";s:3:"web";s:1:"f";s:28:"Voir la liste des directions";s:1:"g";s:12:"organisation";s:1:"h";s:4:"view";s:11:"description";s:28:"Voir la liste des directions";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:39;a:11:{s:1:"a";i:40;s:1:"b";s:29:"organisation.directions.store";s:1:"c";s:3:"web";s:1:"f";s:20:"Créer une direction";s:1:"g";s:12:"organisation";s:1:"h";s:6:"create";s:11:"description";s:20:"Créer une direction";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:40;a:11:{s:1:"a";i:41;s:1:"b";s:30:"organisation.directions.update";s:1:"c";s:3:"web";s:1:"f";s:22:"Modifier une direction";s:1:"g";s:12:"organisation";s:1:"h";s:4:"edit";s:11:"description";s:22:"Modifier une direction";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:41;a:11:{s:1:"a";i:42;s:1:"b";s:31:"organisation.directions.destroy";s:1:"c";s:3:"web";s:1:"f";s:23:"Supprimer une direction";s:1:"g";s:12:"organisation";s:1:"h";s:6:"delete";s:11:"description";s:23:"Supprimer une direction";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:42;a:11:{s:1:"a";i:43;s:1:"b";s:37:"organisation.directions.toggle-status";s:1:"c";s:3:"web";s:1:"f";s:33:"Activer/Désactiver une direction";s:1:"g";s:12:"organisation";s:1:"h";s:6:"toggle";s:11:"description";s:33:"Activer/Désactiver une direction";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:43;a:11:{s:1:"a";i:44;s:1:"b";s:27:"organisation.services.index";s:1:"c";s:3:"web";s:1:"f";s:26:"Voir la liste des services";s:1:"g";s:12:"organisation";s:1:"h";s:4:"view";s:11:"description";s:26:"Voir la liste des services";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:44;a:11:{s:1:"a";i:45;s:1:"b";s:27:"organisation.services.store";s:1:"c";s:3:"web";s:1:"f";s:17:"Créer un service";s:1:"g";s:12:"organisation";s:1:"h";s:6:"create";s:11:"description";s:17:"Créer un service";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:45;a:11:{s:1:"a";i:46;s:1:"b";s:28:"organisation.services.update";s:1:"c";s:3:"web";s:1:"f";s:19:"Modifier un service";s:1:"g";s:12:"organisation";s:1:"h";s:4:"edit";s:11:"description";s:19:"Modifier un service";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:46;a:11:{s:1:"a";i:47;s:1:"b";s:29:"organisation.services.destroy";s:1:"c";s:3:"web";s:1:"f";s:20:"Supprimer un service";s:1:"g";s:12:"organisation";s:1:"h";s:6:"delete";s:11:"description";s:20:"Supprimer un service";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:47;a:11:{s:1:"a";i:48;s:1:"b";s:35:"organisation.services.toggle-status";s:1:"c";s:3:"web";s:1:"f";s:30:"Activer/Désactiver un service";s:1:"g";s:12:"organisation";s:1:"h";s:6:"toggle";s:11:"description";s:30:"Activer/Désactiver un service";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:48;a:11:{s:1:"a";i:49;s:1:"b";s:25:"organisation.unites.index";s:1:"c";s:3:"web";s:1:"f";s:35:"Voir la liste des unités cliniques";s:1:"g";s:12:"organisation";s:1:"h";s:4:"view";s:11:"description";s:35:"Voir la liste des unités cliniques";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:49;a:11:{s:1:"a";i:50;s:1:"b";s:25:"organisation.unites.store";s:1:"c";s:3:"web";s:1:"f";s:26:"Créer une unité clinique";s:1:"g";s:12:"organisation";s:1:"h";s:6:"create";s:11:"description";s:26:"Créer une unité clinique";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:50;a:11:{s:1:"a";i:51;s:1:"b";s:26:"organisation.unites.update";s:1:"c";s:3:"web";s:1:"f";s:28:"Modifier une unité clinique";s:1:"g";s:12:"organisation";s:1:"h";s:4:"edit";s:11:"description";s:28:"Modifier une unité clinique";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:51;a:11:{s:1:"a";i:52;s:1:"b";s:27:"organisation.unites.destroy";s:1:"c";s:3:"web";s:1:"f";s:29:"Supprimer une unité clinique";s:1:"g";s:12:"organisation";s:1:"h";s:6:"delete";s:11:"description";s:29:"Supprimer une unité clinique";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:52;a:11:{s:1:"a";i:53;s:1:"b";s:33:"organisation.unites.toggle-status";s:1:"c";s:3:"web";s:1:"f";s:39:"Activer/Désactiver une unité clinique";s:1:"g";s:12:"organisation";s:1:"h";s:6:"toggle";s:11:"description";s:39:"Activer/Désactiver une unité clinique";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:53;a:11:{s:1:"a";i:54;s:1:"b";s:28:"organisation.batiments.index";s:1:"c";s:3:"web";s:1:"f";s:28:"Voir la liste des bâtiments";s:1:"g";s:12:"organisation";s:1:"h";s:4:"view";s:11:"description";s:28:"Voir la liste des bâtiments";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:54;a:11:{s:1:"a";i:55;s:1:"b";s:28:"organisation.batiments.store";s:1:"c";s:3:"web";s:1:"f";s:19:"Créer un bâtiment";s:1:"g";s:12:"organisation";s:1:"h";s:6:"create";s:11:"description";s:19:"Créer un bâtiment";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:55;a:11:{s:1:"a";i:56;s:1:"b";s:29:"organisation.batiments.update";s:1:"c";s:3:"web";s:1:"f";s:21:"Modifier un bâtiment";s:1:"g";s:12:"organisation";s:1:"h";s:4:"edit";s:11:"description";s:21:"Modifier un bâtiment";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:56;a:11:{s:1:"a";i:57;s:1:"b";s:30:"organisation.batiments.destroy";s:1:"c";s:3:"web";s:1:"f";s:22:"Supprimer un bâtiment";s:1:"g";s:12:"organisation";s:1:"h";s:6:"delete";s:11:"description";s:22:"Supprimer un bâtiment";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:57;a:11:{s:1:"a";i:58;s:1:"b";s:36:"organisation.batiments.toggle-status";s:1:"c";s:3:"web";s:1:"f";s:32:"Activer/Désactiver un bâtiment";s:1:"g";s:12:"organisation";s:1:"h";s:6:"toggle";s:11:"description";s:32:"Activer/Désactiver un bâtiment";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:58;a:11:{s:1:"a";i:59;s:1:"b";s:25:"organisation.etages.index";s:1:"c";s:3:"web";s:1:"f";s:25:"Voir la liste des étages";s:1:"g";s:12:"organisation";s:1:"h";s:4:"view";s:11:"description";s:25:"Voir la liste des étages";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:59;a:11:{s:1:"a";i:60;s:1:"b";s:25:"organisation.etages.store";s:1:"c";s:3:"web";s:1:"f";s:16:"Créer un étage";s:1:"g";s:12:"organisation";s:1:"h";s:6:"create";s:11:"description";s:16:"Créer un étage";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:60;a:11:{s:1:"a";i:61;s:1:"b";s:26:"organisation.etages.update";s:1:"c";s:3:"web";s:1:"f";s:18:"Modifier un étage";s:1:"g";s:12:"organisation";s:1:"h";s:4:"edit";s:11:"description";s:18:"Modifier un étage";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:61;a:11:{s:1:"a";i:62;s:1:"b";s:27:"organisation.etages.destroy";s:1:"c";s:3:"web";s:1:"f";s:19:"Supprimer un étage";s:1:"g";s:12:"organisation";s:1:"h";s:6:"delete";s:11:"description";s:19:"Supprimer un étage";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:62;a:11:{s:1:"a";i:63;s:1:"b";s:33:"organisation.etages.toggle-status";s:1:"c";s:3:"web";s:1:"f";s:29:"Activer/Désactiver un étage";s:1:"g";s:12:"organisation";s:1:"h";s:6:"toggle";s:11:"description";s:29:"Activer/Désactiver un étage";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:63;a:11:{s:1:"a";i:64;s:1:"b";s:25:"organisation.locaux.index";s:1:"c";s:3:"web";s:1:"f";s:24:"Voir la liste des locaux";s:1:"g";s:12:"organisation";s:1:"h";s:4:"view";s:11:"description";s:24:"Voir la liste des locaux";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:64;a:11:{s:1:"a";i:65;s:1:"b";s:25:"organisation.locaux.store";s:1:"c";s:3:"web";s:1:"f";s:15:"Créer un local";s:1:"g";s:12:"organisation";s:1:"h";s:6:"create";s:11:"description";s:15:"Créer un local";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:65;a:11:{s:1:"a";i:66;s:1:"b";s:26:"organisation.locaux.update";s:1:"c";s:3:"web";s:1:"f";s:17:"Modifier un local";s:1:"g";s:12:"organisation";s:1:"h";s:4:"edit";s:11:"description";s:17:"Modifier un local";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:66;a:11:{s:1:"a";i:67;s:1:"b";s:27:"organisation.locaux.destroy";s:1:"c";s:3:"web";s:1:"f";s:18:"Supprimer un local";s:1:"g";s:12:"organisation";s:1:"h";s:6:"delete";s:11:"description";s:18:"Supprimer un local";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:67;a:11:{s:1:"a";i:68;s:1:"b";s:33:"organisation.locaux.toggle-status";s:1:"c";s:3:"web";s:1:"f";s:28:"Activer/Désactiver un local";s:1:"g";s:12:"organisation";s:1:"h";s:6:"toggle";s:11:"description";s:28:"Activer/Désactiver un local";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:68;a:11:{s:1:"a";i:69;s:1:"b";s:25:"organisation.postes.index";s:1:"c";s:3:"web";s:1:"f";s:35:"Voir la liste des postes de travail";s:1:"g";s:12:"organisation";s:1:"h";s:4:"view";s:11:"description";s:35:"Voir la liste des postes de travail";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:69;a:11:{s:1:"a";i:70;s:1:"b";s:25:"organisation.postes.store";s:1:"c";s:3:"web";s:1:"f";s:26:"Créer un poste de travail";s:1:"g";s:12:"organisation";s:1:"h";s:6:"create";s:11:"description";s:26:"Créer un poste de travail";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:70;a:11:{s:1:"a";i:71;s:1:"b";s:26:"organisation.postes.update";s:1:"c";s:3:"web";s:1:"f";s:28:"Modifier un poste de travail";s:1:"g";s:12:"organisation";s:1:"h";s:4:"edit";s:11:"description";s:28:"Modifier un poste de travail";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:71;a:11:{s:1:"a";i:72;s:1:"b";s:27:"organisation.postes.destroy";s:1:"c";s:3:"web";s:1:"f";s:29:"Supprimer un poste de travail";s:1:"g";s:12:"organisation";s:1:"h";s:6:"delete";s:11:"description";s:29:"Supprimer un poste de travail";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:72;a:11:{s:1:"a";i:73;s:1:"b";s:33:"organisation.postes.toggle-status";s:1:"c";s:3:"web";s:1:"f";s:39:"Activer/Désactiver un poste de travail";s:1:"g";s:12:"organisation";s:1:"h";s:6:"toggle";s:11:"description";s:39:"Activer/Désactiver un poste de travail";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:73;a:11:{s:1:"a";i:74;s:1:"b";s:23:"parcinfo.dashboard.view";s:1:"c";s:3:"web";s:1:"f";s:33:"Voir le tableau de bord Parc Info";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"view";s:11:"description";s:33:"Voir le tableau de bord Parc Info";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:74;a:11:{s:1:"a";i:75;s:1:"b";s:26:"parcinfo.ordinateurs.index";s:1:"c";s:3:"web";s:1:"f";s:29:"Voir la liste des ordinateurs";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"view";s:11:"description";s:29:"Voir la liste des ordinateurs";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:75;a:11:{s:1:"a";i:76;s:1:"b";s:26:"parcinfo.ordinateurs.store";s:1:"c";s:3:"web";s:1:"f";s:20:"Créer un ordinateur";s:1:"g";s:8:"parcinfo";s:1:"h";s:6:"create";s:11:"description";s:20:"Créer un ordinateur";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:76;a:11:{s:1:"a";i:77;s:1:"b";s:27:"parcinfo.ordinateurs.update";s:1:"c";s:3:"web";s:1:"f";s:22:"Modifier un ordinateur";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"edit";s:11:"description";s:22:"Modifier un ordinateur";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:77;a:11:{s:1:"a";i:78;s:1:"b";s:28:"parcinfo.ordinateurs.destroy";s:1:"c";s:3:"web";s:1:"f";s:23:"Supprimer un ordinateur";s:1:"g";s:8:"parcinfo";s:1:"h";s:6:"delete";s:11:"description";s:23:"Supprimer un ordinateur";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:78;a:11:{s:1:"a";i:79;s:1:"b";s:23:"parcinfo.serveurs.index";s:1:"c";s:3:"web";s:1:"f";s:26:"Voir la liste des serveurs";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"view";s:11:"description";s:26:"Voir la liste des serveurs";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:79;a:11:{s:1:"a";i:80;s:1:"b";s:23:"parcinfo.serveurs.store";s:1:"c";s:3:"web";s:1:"f";s:17:"Créer un serveur";s:1:"g";s:8:"parcinfo";s:1:"h";s:6:"create";s:11:"description";s:17:"Créer un serveur";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:80;a:11:{s:1:"a";i:81;s:1:"b";s:24:"parcinfo.serveurs.update";s:1:"c";s:3:"web";s:1:"f";s:19:"Modifier un serveur";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"edit";s:11:"description";s:19:"Modifier un serveur";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:81;a:11:{s:1:"a";i:82;s:1:"b";s:25:"parcinfo.serveurs.destroy";s:1:"c";s:3:"web";s:1:"f";s:20:"Supprimer un serveur";s:1:"g";s:8:"parcinfo";s:1:"h";s:6:"delete";s:11:"description";s:20:"Supprimer un serveur";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:82;a:11:{s:1:"a";i:83;s:1:"b";s:22:"parcinfo.mobiles.index";s:1:"c";s:3:"web";s:1:"f";s:35:"Voir la liste des terminaux mobiles";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"view";s:11:"description";s:35:"Voir la liste des terminaux mobiles";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:83;a:11:{s:1:"a";i:84;s:1:"b";s:22:"parcinfo.mobiles.store";s:1:"c";s:3:"web";s:1:"f";s:25:"Créer un terminal mobile";s:1:"g";s:8:"parcinfo";s:1:"h";s:6:"create";s:11:"description";s:25:"Créer un terminal mobile";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:84;a:11:{s:1:"a";i:85;s:1:"b";s:23:"parcinfo.mobiles.update";s:1:"c";s:3:"web";s:1:"f";s:27:"Modifier un terminal mobile";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"edit";s:11:"description";s:27:"Modifier un terminal mobile";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:85;a:11:{s:1:"a";i:86;s:1:"b";s:24:"parcinfo.mobiles.destroy";s:1:"c";s:3:"web";s:1:"f";s:28:"Supprimer un terminal mobile";s:1:"g";s:8:"parcinfo";s:1:"h";s:6:"delete";s:11:"description";s:28:"Supprimer un terminal mobile";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:86;a:11:{s:1:"a";i:87;s:1:"b";s:24:"parcinfo.logiciels.index";s:1:"c";s:3:"web";s:1:"f";s:27:"Voir le catalogue logiciels";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"view";s:11:"description";s:27:"Voir le catalogue logiciels";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:87;a:11:{s:1:"a";i:88;s:1:"b";s:24:"parcinfo.logiciels.store";s:1:"c";s:3:"web";s:1:"f";s:19:"Ajouter un logiciel";s:1:"g";s:8:"parcinfo";s:1:"h";s:6:"create";s:11:"description";s:19:"Ajouter un logiciel";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:88;a:11:{s:1:"a";i:89;s:1:"b";s:25:"parcinfo.logiciels.update";s:1:"c";s:3:"web";s:1:"f";s:20:"Modifier un logiciel";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"edit";s:11:"description";s:20:"Modifier un logiciel";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:89;a:11:{s:1:"a";i:90;s:1:"b";s:26:"parcinfo.logiciels.destroy";s:1:"c";s:3:"web";s:1:"f";s:21:"Supprimer un logiciel";s:1:"g";s:8:"parcinfo";s:1:"h";s:6:"delete";s:11:"description";s:21:"Supprimer un logiciel";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:90;a:11:{s:1:"a";i:91;s:1:"b";s:27:"parcinfo.fournisseurs.index";s:1:"c";s:3:"web";s:1:"f";s:30:"Voir la liste des fournisseurs";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"view";s:11:"description";s:30:"Voir la liste des fournisseurs";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:91;a:11:{s:1:"a";i:92;s:1:"b";s:27:"parcinfo.fournisseurs.store";s:1:"c";s:3:"web";s:1:"f";s:21:"Créer un fournisseur";s:1:"g";s:8:"parcinfo";s:1:"h";s:6:"create";s:11:"description";s:21:"Créer un fournisseur";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:92;a:11:{s:1:"a";i:93;s:1:"b";s:28:"parcinfo.fournisseurs.update";s:1:"c";s:3:"web";s:1:"f";s:23:"Modifier un fournisseur";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"edit";s:11:"description";s:23:"Modifier un fournisseur";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:93;a:11:{s:1:"a";i:94;s:1:"b";s:29:"parcinfo.fournisseurs.destroy";s:1:"c";s:3:"web";s:1:"f";s:24:"Supprimer un fournisseur";s:1:"g";s:8:"parcinfo";s:1:"h";s:6:"delete";s:11:"description";s:24:"Supprimer un fournisseur";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:94;a:11:{s:1:"a";i:95;s:1:"b";s:23:"parcinfo.licences.index";s:1:"c";s:3:"web";s:1:"f";s:26:"Voir la liste des licences";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"view";s:11:"description";s:26:"Voir la liste des licences";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:95;a:11:{s:1:"a";i:96;s:1:"b";s:23:"parcinfo.licences.store";s:1:"c";s:3:"web";s:1:"f";s:18:"Créer une licence";s:1:"g";s:8:"parcinfo";s:1:"h";s:6:"create";s:11:"description";s:18:"Créer une licence";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:96;a:11:{s:1:"a";i:97;s:1:"b";s:24:"parcinfo.licences.update";s:1:"c";s:3:"web";s:1:"f";s:20:"Modifier une licence";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"edit";s:11:"description";s:20:"Modifier une licence";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:97;a:11:{s:1:"a";i:98;s:1:"b";s:25:"parcinfo.licences.destroy";s:1:"c";s:3:"web";s:1:"f";s:21:"Supprimer une licence";s:1:"g";s:8:"parcinfo";s:1:"h";s:6:"delete";s:11:"description";s:21:"Supprimer une licence";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:98;a:11:{s:1:"a";i:99;s:1:"b";s:27:"parcinfo.consommables.index";s:1:"c";s:3:"web";s:1:"f";s:26:"Voir le stock consommables";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"view";s:11:"description";s:26:"Voir le stock consommables";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:99;a:11:{s:1:"a";i:100;s:1:"b";s:27:"parcinfo.consommables.store";s:1:"c";s:3:"web";s:1:"f";s:22:"Ajouter un consommable";s:1:"g";s:8:"parcinfo";s:1:"h";s:6:"create";s:11:"description";s:22:"Ajouter un consommable";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:100;a:11:{s:1:"a";i:101;s:1:"b";s:28:"parcinfo.consommables.update";s:1:"c";s:3:"web";s:1:"f";s:23:"Modifier un consommable";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"edit";s:11:"description";s:23:"Modifier un consommable";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:101;a:11:{s:1:"a";i:102;s:1:"b";s:29:"parcinfo.consommables.destroy";s:1:"c";s:3:"web";s:1:"f";s:24:"Supprimer un consommable";s:1:"g";s:8:"parcinfo";s:1:"h";s:6:"delete";s:11:"description";s:24:"Supprimer un consommable";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:102;a:11:{s:1:"a";i:103;s:1:"b";s:23:"parcinfo.switches.index";s:1:"c";s:3:"web";s:1:"f";s:26:"Voir la liste des switches";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"view";s:11:"description";s:26:"Voir la liste des switches";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:103;a:11:{s:1:"a";i:104;s:1:"b";s:23:"parcinfo.routeurs.index";s:1:"c";s:3:"web";s:1:"f";s:26:"Voir la liste des routeurs";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"view";s:11:"description";s:26:"Voir la liste des routeurs";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:104;a:11:{s:1:"a";i:105;s:1:"b";s:19:"parcinfo.wifi.index";s:1:"c";s:3:"web";s:1:"f";s:38:"Voir la liste des points d'accès WiFi";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"view";s:11:"description";s:38:"Voir la liste des points d'accès WiFi";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:105;a:11:{s:1:"a";i:106;s:1:"b";s:23:"parcinfo.parefeux.index";s:1:"c";s:3:"web";s:1:"f";s:27:"Voir la liste des pare-feux";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"view";s:11:"description";s:27:"Voir la liste des pare-feux";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:106;a:11:{s:1:"a";i:107;s:1:"b";s:24:"parcinfo.onduleurs.index";s:1:"c";s:3:"web";s:1:"f";s:27:"Voir la liste des onduleurs";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"view";s:11:"description";s:27:"Voir la liste des onduleurs";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:107;a:11:{s:1:"a";i:108;s:1:"b";s:20:"parcinfo.racks.index";s:1:"c";s:3:"web";s:1:"f";s:31:"Voir la liste des baies & racks";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"view";s:11:"description";s:31:"Voir la liste des baies & racks";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:108;a:11:{s:1:"a";i:109;s:1:"b";s:23:"parcinfo.brassage.index";s:1:"c";s:3:"web";s:1:"f";s:25:"Voir la liste de brassage";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"view";s:11:"description";s:25:"Voir la liste de brassage";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:109;a:11:{s:1:"a";i:110;s:1:"b";s:22:"parcinfo.cameras.index";s:1:"c";s:3:"web";s:1:"f";s:29:"Voir la liste des caméras IP";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"view";s:11:"description";s:29:"Voir la liste des caméras IP";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:110;a:11:{s:1:"a";i:111;s:1:"b";s:26:"parcinfo.imprimantes.index";s:1:"c";s:3:"web";s:1:"f";s:29:"Voir la liste des imprimantes";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"view";s:11:"description";s:29:"Voir la liste des imprimantes";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:111;a:11:{s:1:"a";i:112;s:1:"b";s:23:"parcinfo.scanners.index";s:1:"c";s:3:"web";s:1:"f";s:26:"Voir la liste des scanners";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"view";s:11:"description";s:26:"Voir la liste des scanners";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:112;a:11:{s:1:"a";i:113;s:1:"b";s:25:"parcinfo.telephonie.index";s:1:"c";s:3:"web";s:1:"f";s:36:"Voir la liste des téléphones fixes";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"view";s:11:"description";s:36:"Voir la liste des téléphones fixes";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:113;a:11:{s:1:"a";i:114;s:1:"b";s:27:"parcinfo.terminaux-ip.index";s:1:"c";s:3:"web";s:1:"f";s:30:"Voir la liste des terminaux IP";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"view";s:11:"description";s:30:"Voir la liste des terminaux IP";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;s:1:"r";a:1:{i:0;i:1;}}i:114;a:10:{s:1:"a";i:115;s:1:"b";s:39:"parc-info.referentiels.types-cpus.index";s:1:"c";s:3:"web";s:1:"f";s:37:"Référentiel - Voir les types de CPU";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"view";s:11:"description";s:37:"Référentiel - Voir les types de CPU";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:115;a:10:{s:1:"a";i:116;s:1:"b";s:39:"parc-info.referentiels.types-cpus.store";s:1:"c";s:3:"web";s:1:"f";s:37:"Référentiel - Créer un type de CPU";s:1:"g";s:8:"parcinfo";s:1:"h";s:6:"create";s:11:"description";s:37:"Référentiel - Créer un type de CPU";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:116;a:10:{s:1:"a";i:117;s:1:"b";s:40:"parc-info.referentiels.types-cpus.update";s:1:"c";s:3:"web";s:1:"f";s:39:"Référentiel - Modifier un type de CPU";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"edit";s:11:"description";s:39:"Référentiel - Modifier un type de CPU";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:117;a:10:{s:1:"a";i:118;s:1:"b";s:41:"parc-info.referentiels.types-cpus.destroy";s:1:"c";s:3:"web";s:1:"f";s:40:"Référentiel - Supprimer un type de CPU";s:1:"g";s:8:"parcinfo";s:1:"h";s:6:"delete";s:11:"description";s:40:"Référentiel - Supprimer un type de CPU";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:118;a:10:{s:1:"a";i:119;s:1:"b";s:42:"parc-info.referentiels.types-disques.index";s:1:"c";s:3:"web";s:1:"f";s:40:"Référentiel - Voir les types de disque";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"view";s:11:"description";s:40:"Référentiel - Voir les types de disque";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:119;a:10:{s:1:"a";i:120;s:1:"b";s:42:"parc-info.referentiels.types-disques.store";s:1:"c";s:3:"web";s:1:"f";s:40:"Référentiel - Créer un type de disque";s:1:"g";s:8:"parcinfo";s:1:"h";s:6:"create";s:11:"description";s:40:"Référentiel - Créer un type de disque";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:120;a:10:{s:1:"a";i:121;s:1:"b";s:43:"parc-info.referentiels.types-disques.update";s:1:"c";s:3:"web";s:1:"f";s:42:"Référentiel - Modifier un type de disque";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"edit";s:11:"description";s:42:"Référentiel - Modifier un type de disque";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:121;a:10:{s:1:"a";i:122;s:1:"b";s:44:"parc-info.referentiels.types-disques.destroy";s:1:"c";s:3:"web";s:1:"f";s:43:"Référentiel - Supprimer un type de disque";s:1:"g";s:8:"parcinfo";s:1:"h";s:6:"delete";s:11:"description";s:43:"Référentiel - Supprimer un type de disque";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:122;a:10:{s:1:"a";i:123;s:1:"b";s:37:"parc-info.referentiels.types-os.index";s:1:"c";s:3:"web";s:1:"f";s:35:"Référentiel - Voir les types d'OS";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"view";s:11:"description";s:35:"Référentiel - Voir les types d'OS";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:123;a:10:{s:1:"a";i:124;s:1:"b";s:37:"parc-info.referentiels.types-os.store";s:1:"c";s:3:"web";s:1:"f";s:35:"Référentiel - Créer un type d'OS";s:1:"g";s:8:"parcinfo";s:1:"h";s:6:"create";s:11:"description";s:35:"Référentiel - Créer un type d'OS";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:124;a:10:{s:1:"a";i:125;s:1:"b";s:38:"parc-info.referentiels.types-os.update";s:1:"c";s:3:"web";s:1:"f";s:37:"Référentiel - Modifier un type d'OS";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"edit";s:11:"description";s:37:"Référentiel - Modifier un type d'OS";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:125;a:10:{s:1:"a";i:126;s:1:"b";s:39:"parc-info.referentiels.types-os.destroy";s:1:"c";s:3:"web";s:1:"f";s:38:"Référentiel - Supprimer un type d'OS";s:1:"g";s:8:"parcinfo";s:1:"h";s:6:"delete";s:11:"description";s:38:"Référentiel - Supprimer un type d'OS";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:126;a:10:{s:1:"a";i:127;s:1:"b";s:39:"parc-info.referentiels.types-rams.index";s:1:"c";s:3:"web";s:1:"f";s:37:"Référentiel - Voir les types de RAM";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"view";s:11:"description";s:37:"Référentiel - Voir les types de RAM";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:127;a:10:{s:1:"a";i:128;s:1:"b";s:39:"parc-info.referentiels.types-rams.store";s:1:"c";s:3:"web";s:1:"f";s:37:"Référentiel - Créer un type de RAM";s:1:"g";s:8:"parcinfo";s:1:"h";s:6:"create";s:11:"description";s:37:"Référentiel - Créer un type de RAM";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:128;a:10:{s:1:"a";i:129;s:1:"b";s:40:"parc-info.referentiels.types-rams.update";s:1:"c";s:3:"web";s:1:"f";s:39:"Référentiel - Modifier un type de RAM";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"edit";s:11:"description";s:39:"Référentiel - Modifier un type de RAM";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:129;a:10:{s:1:"a";i:130;s:1:"b";s:41:"parc-info.referentiels.types-rams.destroy";s:1:"c";s:3:"web";s:1:"f";s:40:"Référentiel - Supprimer un type de RAM";s:1:"g";s:8:"parcinfo";s:1:"h";s:6:"delete";s:11:"description";s:40:"Référentiel - Supprimer un type de RAM";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:130;a:10:{s:1:"a";i:131;s:1:"b";s:36:"parc-info.referentiels.marques.index";s:1:"c";s:3:"web";s:1:"f";s:32:"Référentiel - Voir les marques";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"view";s:11:"description";s:32:"Référentiel - Voir les marques";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:131;a:10:{s:1:"a";i:132;s:1:"b";s:36:"parc-info.referentiels.marques.store";s:1:"c";s:3:"web";s:1:"f";s:33:"Référentiel - Créer une marque";s:1:"g";s:8:"parcinfo";s:1:"h";s:6:"create";s:11:"description";s:33:"Référentiel - Créer une marque";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:132;a:10:{s:1:"a";i:133;s:1:"b";s:37:"parc-info.referentiels.marques.update";s:1:"c";s:3:"web";s:1:"f";s:35:"Référentiel - Modifier une marque";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"edit";s:11:"description";s:35:"Référentiel - Modifier une marque";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:133;a:10:{s:1:"a";i:134;s:1:"b";s:38:"parc-info.referentiels.marques.destroy";s:1:"c";s:3:"web";s:1:"f";s:36:"Référentiel - Supprimer une marque";s:1:"g";s:8:"parcinfo";s:1:"h";s:6:"delete";s:11:"description";s:36:"Référentiel - Supprimer une marque";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:134;a:10:{s:1:"a";i:135;s:1:"b";s:46:"parc-info.referentiels.types-imprimantes.index";s:1:"c";s:3:"web";s:1:"f";s:43:"Référentiel - Voir les types d'imprimante";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"view";s:11:"description";s:43:"Référentiel - Voir les types d'imprimante";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:135;a:10:{s:1:"a";i:136;s:1:"b";s:46:"parc-info.referentiels.types-imprimantes.store";s:1:"c";s:3:"web";s:1:"f";s:43:"Référentiel - Créer un type d'imprimante";s:1:"g";s:8:"parcinfo";s:1:"h";s:6:"create";s:11:"description";s:43:"Référentiel - Créer un type d'imprimante";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:136;a:10:{s:1:"a";i:137;s:1:"b";s:47:"parc-info.referentiels.types-imprimantes.update";s:1:"c";s:3:"web";s:1:"f";s:45:"Référentiel - Modifier un type d'imprimante";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"edit";s:11:"description";s:45:"Référentiel - Modifier un type d'imprimante";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:137;a:10:{s:1:"a";i:138;s:1:"b";s:48:"parc-info.referentiels.types-imprimantes.destroy";s:1:"c";s:3:"web";s:1:"f";s:46:"Référentiel - Supprimer un type d'imprimante";s:1:"g";s:8:"parcinfo";s:1:"h";s:6:"delete";s:11:"description";s:46:"Référentiel - Supprimer un type d'imprimante";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:138;a:10:{s:1:"a";i:139;s:1:"b";s:42:"parc-info.referentiels.types-mobiles.index";s:1:"c";s:3:"web";s:1:"f";s:40:"Référentiel - Voir les types de mobile";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"view";s:11:"description";s:40:"Référentiel - Voir les types de mobile";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:139;a:10:{s:1:"a";i:140;s:1:"b";s:42:"parc-info.referentiels.types-mobiles.store";s:1:"c";s:3:"web";s:1:"f";s:40:"Référentiel - Créer un type de mobile";s:1:"g";s:8:"parcinfo";s:1:"h";s:6:"create";s:11:"description";s:40:"Référentiel - Créer un type de mobile";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:140;a:10:{s:1:"a";i:141;s:1:"b";s:43:"parc-info.referentiels.types-mobiles.update";s:1:"c";s:3:"web";s:1:"f";s:42:"Référentiel - Modifier un type de mobile";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"edit";s:11:"description";s:42:"Référentiel - Modifier un type de mobile";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:141;a:10:{s:1:"a";i:142;s:1:"b";s:44:"parc-info.referentiels.types-mobiles.destroy";s:1:"c";s:3:"web";s:1:"f";s:43:"Référentiel - Supprimer un type de mobile";s:1:"g";s:8:"parcinfo";s:1:"h";s:6:"delete";s:11:"description";s:43:"Référentiel - Supprimer un type de mobile";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:142;a:10:{s:1:"a";i:143;s:1:"b";s:42:"parc-info.referentiels.types-reseaux.index";s:1:"c";s:3:"web";s:1:"f";s:52:"Référentiel - Voir les types d'équipement réseau";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"view";s:11:"description";s:52:"Référentiel - Voir les types d'équipement réseau";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:143;a:10:{s:1:"a";i:144;s:1:"b";s:42:"parc-info.referentiels.types-reseaux.store";s:1:"c";s:3:"web";s:1:"f";s:52:"Référentiel - Créer un type d'équipement réseau";s:1:"g";s:8:"parcinfo";s:1:"h";s:6:"create";s:11:"description";s:52:"Référentiel - Créer un type d'équipement réseau";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:144;a:10:{s:1:"a";i:145;s:1:"b";s:43:"parc-info.referentiels.types-reseaux.update";s:1:"c";s:3:"web";s:1:"f";s:54:"Référentiel - Modifier un type d'équipement réseau";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"edit";s:11:"description";s:54:"Référentiel - Modifier un type d'équipement réseau";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:145;a:10:{s:1:"a";i:146;s:1:"b";s:44:"parc-info.referentiels.types-reseaux.destroy";s:1:"c";s:3:"web";s:1:"f";s:55:"Référentiel - Supprimer un type d'équipement réseau";s:1:"g";s:8:"parcinfo";s:1:"h";s:6:"delete";s:11:"description";s:55:"Référentiel - Supprimer un type d'équipement réseau";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:146;a:10:{s:1:"a";i:147;s:1:"b";s:50:"parc-info.referentiels.types-infrastructures.index";s:1:"c";s:3:"web";s:1:"f";s:47:"Référentiel - Voir les types d'infrastructure";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"view";s:11:"description";s:47:"Référentiel - Voir les types d'infrastructure";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:147;a:10:{s:1:"a";i:148;s:1:"b";s:50:"parc-info.referentiels.types-infrastructures.store";s:1:"c";s:3:"web";s:1:"f";s:47:"Référentiel - Créer un type d'infrastructure";s:1:"g";s:8:"parcinfo";s:1:"h";s:6:"create";s:11:"description";s:47:"Référentiel - Créer un type d'infrastructure";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:148;a:10:{s:1:"a";i:149;s:1:"b";s:51:"parc-info.referentiels.types-infrastructures.update";s:1:"c";s:3:"web";s:1:"f";s:49:"Référentiel - Modifier un type d'infrastructure";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"edit";s:11:"description";s:49:"Référentiel - Modifier un type d'infrastructure";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:149;a:10:{s:1:"a";i:150;s:1:"b";s:52:"parc-info.referentiels.types-infrastructures.destroy";s:1:"c";s:3:"web";s:1:"f";s:50:"Référentiel - Supprimer un type d'infrastructure";s:1:"g";s:8:"parcinfo";s:1:"h";s:6:"delete";s:11:"description";s:50:"Référentiel - Supprimer un type d'infrastructure";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:150;a:10:{s:1:"a";i:151;s:1:"b";s:43:"parc-info.referentiels.types-licences.index";s:1:"c";s:3:"web";s:1:"f";s:41:"Référentiel - Voir les types de licence";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"view";s:11:"description";s:41:"Référentiel - Voir les types de licence";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:151;a:10:{s:1:"a";i:152;s:1:"b";s:43:"parc-info.referentiels.types-licences.store";s:1:"c";s:3:"web";s:1:"f";s:41:"Référentiel - Créer un type de licence";s:1:"g";s:8:"parcinfo";s:1:"h";s:6:"create";s:11:"description";s:41:"Référentiel - Créer un type de licence";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:152;a:10:{s:1:"a";i:153;s:1:"b";s:44:"parc-info.referentiels.types-licences.update";s:1:"c";s:3:"web";s:1:"f";s:43:"Référentiel - Modifier un type de licence";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"edit";s:11:"description";s:43:"Référentiel - Modifier un type de licence";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:153;a:10:{s:1:"a";i:154;s:1:"b";s:45:"parc-info.referentiels.types-licences.destroy";s:1:"c";s:3:"web";s:1:"f";s:44:"Référentiel - Supprimer un type de licence";s:1:"g";s:8:"parcinfo";s:1:"h";s:6:"delete";s:11:"description";s:44:"Référentiel - Supprimer un type de licence";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:154;a:10:{s:1:"a";i:155;s:1:"b";s:47:"parc-info.referentiels.types-consommables.index";s:1:"c";s:3:"web";s:1:"f";s:45:"Référentiel - Voir les types de consommable";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"view";s:11:"description";s:45:"Référentiel - Voir les types de consommable";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:155;a:10:{s:1:"a";i:156;s:1:"b";s:47:"parc-info.referentiels.types-consommables.store";s:1:"c";s:3:"web";s:1:"f";s:45:"Référentiel - Créer un type de consommable";s:1:"g";s:8:"parcinfo";s:1:"h";s:6:"create";s:11:"description";s:45:"Référentiel - Créer un type de consommable";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:156;a:10:{s:1:"a";i:157;s:1:"b";s:48:"parc-info.referentiels.types-consommables.update";s:1:"c";s:3:"web";s:1:"f";s:47:"Référentiel - Modifier un type de consommable";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"edit";s:11:"description";s:47:"Référentiel - Modifier un type de consommable";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:157;a:10:{s:1:"a";i:158;s:1:"b";s:49:"parc-info.referentiels.types-consommables.destroy";s:1:"c";s:3:"web";s:1:"f";s:48:"Référentiel - Supprimer un type de consommable";s:1:"g";s:8:"parcinfo";s:1:"h";s:6:"delete";s:11:"description";s:48:"Référentiel - Supprimer un type de consommable";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:158;a:10:{s:1:"a";i:159;s:1:"b";s:37:"parc-info.referentiels.editeurs.index";s:1:"c";s:3:"web";s:1:"f";s:34:"Référentiel - Voir les éditeurs";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"view";s:11:"description";s:34:"Référentiel - Voir les éditeurs";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:159;a:10:{s:1:"a";i:160;s:1:"b";s:37:"parc-info.referentiels.editeurs.store";s:1:"c";s:3:"web";s:1:"f";s:34:"Référentiel - Créer un éditeur";s:1:"g";s:8:"parcinfo";s:1:"h";s:6:"create";s:11:"description";s:34:"Référentiel - Créer un éditeur";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:160;a:10:{s:1:"a";i:161;s:1:"b";s:38:"parc-info.referentiels.editeurs.update";s:1:"c";s:3:"web";s:1:"f";s:36:"Référentiel - Modifier un éditeur";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"edit";s:11:"description";s:36:"Référentiel - Modifier un éditeur";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:161;a:10:{s:1:"a";i:162;s:1:"b";s:39:"parc-info.referentiels.editeurs.destroy";s:1:"c";s:3:"web";s:1:"f";s:37:"Référentiel - Supprimer un éditeur";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"edit";s:11:"description";s:37:"Référentiel - Supprimer un éditeur";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:162;a:10:{s:1:"a";i:163;s:1:"b";s:28:"parc-info.analyse.etats.view";s:1:"c";s:3:"web";s:1:"f";s:42:"Analyse - Voir les états des équipements";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"view";s:11:"description";s:42:"Analyse - Voir les états des équipements";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}i:163;a:10:{s:1:"a";i:164;s:1:"b";s:35:"parc-info.analyse.statistiques.view";s:1:"c";s:3:"web";s:1:"f";s:31:"Analyse - Voir les statistiques";s:1:"g";s:8:"parcinfo";s:1:"h";s:4:"view";s:11:"description";s:31:"Analyse - Voir les statistiques";s:5:"group";N;s:10:"sort_order";i:0;s:10:"is_visible";b:1;}}s:5:"roles";a:2:{i:0;a:4:{s:1:"a";i:1;s:1:"b";s:11:"super-admin";s:1:"c";s:3:"web";s:11:"description";N;}i:1;a:4:{s:1:"a";i:2;s:1:"b";s:5:"admin";s:1:"c";s:3:"web";s:11:"description";N;}}}	1781689089
\.


--
-- Data for Name: cache_locks; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.cache_locks (key, owner, expiration) FROM stdin;
\.


--
-- Data for Name: failed_jobs; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.failed_jobs (id, uuid, connection, queue, payload, exception, failed_at) FROM stdin;
\.


--
-- Data for Name: grh_contacts_employes; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.grh_contacts_employes (id, employe_id, type_contact, valeur, est_whatsapp, created_at, updated_at) FROM stdin;
1	1	email	issaka.sawadogo@chu-yo.bf	f	2026-05-21 19:39:03	2026-05-21 19:39:03
2	1	telephone	70000001	f	2026-05-21 19:39:03	2026-05-21 19:39:03
3	2	email	elise.da@chu-yo.bf	f	2026-05-21 19:39:03	2026-05-21 19:39:03
4	2	telephone	70000002	f	2026-05-21 19:39:03	2026-05-21 19:39:03
5	3	email	adama.oued@chu-yo.bf	f	2026-05-21 19:39:03	2026-05-21 19:39:03
6	3	telephone	70000003	f	2026-05-21 19:39:03	2026-05-21 19:39:03
7	4	email	souley.traore@chu-yo.bf	f	2026-05-21 19:39:03	2026-05-21 19:39:03
8	4	telephone	70000004	f	2026-05-21 19:39:03	2026-05-21 19:39:03
9	5	email	fatou.zongo@chu-yo.bf	f	2026-05-21 19:39:03	2026-05-21 19:39:03
10	5	telephone	70000005	f	2026-05-21 19:39:03	2026-05-21 19:39:03
\.


--
-- Data for Name: grh_dossiers_employes; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.grh_dossiers_employes (id, matricule, nom, prenom, date_naissance, genre, date_embauche, poste, est_actif, niveau_rattachement, direction_id, service_id, unite_id, created_at, updated_at) FROM stdin;
1	M001	SAWADOGO	Issaka	\N	M	2020-01-15	Directeur Général	t	direction	13	\N	\N	2026-05-21 19:39:03	2026-05-21 19:39:03
2	M002	DA ATIAN	Elise	\N	F	2025-05-20	Directrice des Soins Infirmiers et Obstétricaux	t	direction	3	\N	\N	2026-05-21 19:39:03	2026-05-21 19:39:03
3	M003	OUEDRAOGO	Adama	\N	M	2021-06-10	Directeur des Services Informatiques	t	direction	11	\N	\N	2026-05-21 19:39:03	2026-05-21 19:39:03
4	M004	TRAORE	Souleymane	\N	M	\N	Technicien Supérieur en Informatique	t	service	11	19	\N	2026-05-21 19:39:03	2026-05-21 19:39:03
5	M005	ZONGO	Fatoumata	\N	F	\N	Infirmière Chef	t	unite	2	39	1	2026-05-21 19:39:03	2026-05-21 19:39:03
\.


--
-- Data for Name: job_batches; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.job_batches (id, name, total_jobs, pending_jobs, failed_jobs, failed_job_ids, options, cancelled_at, created_at, finished_at) FROM stdin;
\.


--
-- Data for Name: jobs; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.jobs (id, queue, payload, attempts, reserved_at, available_at, created_at) FROM stdin;
\.


--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	0001_01_01_000000_create_users_table	1
2	0001_01_01_000001_create_cache_table	1
3	0001_01_01_000002_create_jobs_table	1
4	2025_12_18_214056_create_permission_tables	1
5	2025_12_24_103711_create_cores_users_table	1
6	2025_12_24_151836_add_is_active_to_users_table	1
7	2025_12_26_034554_replace_users_table_with_cores_users	1
8	2025_12_26_042753_add_label_to_permissions_table	1
9	2026_01_08_110151_create_modules_table	1
10	2026_01_08_113203_add_module_field_to_permissions_table	1
11	2026_01_08_114055_add_modules_fileds_to_permissions	1
12	2026_01_14_120808_add_avatar_to_users_table	1
13	2026_01_14_201208_add_description_to_roles_table	1
14	2026_01_15_211310_create_activity_log_table	1
15	2026_01_15_211311_add_event_column_to_activity_log_table	1
16	2026_01_15_211312_add_batch_uuid_column_to_activity_log_table	1
17	2026_01_15_213029_add_module_to_activity_log_table	1
18	2026_01_15_232214_add_roles_expiration_to_activity_log	1
19	2026_01_16_005035_fix_activity_log_json_index	1
20	2026_02_17_120001_create_organisation_sites_table	1
21	2026_02_17_120002_create_organisation_directions_table	1
22	2026_02_17_120003_create_organisation_services_table	1
23	2026_02_17_120004_create_organisation_unites_table	1
24	2026_03_10_150000_create_organisation_batiments_table	1
25	2026_03_10_150001_create_organisation_etages_table	1
26	2026_03_10_150002_create_organisation_locaux_table	1
27	2026_03_13_194023_create_organisation_postes_travail_table	1
28	2026_03_14_045206_add_type_service_to_organisation_services_table	1
29	2026_03_28_121211_create_employes_table	1
30	2026_03_28_180416_update_organisation_postes_travail_table	1
31	2026_03_29_000001_create_parc_info_tables	1
32	2026_04_22_164204_update_organisation_responsables_to_employes	1
33	2026_04_24_041115_add_dossier_employe_id_to_users_table	1
34	2026_05_14_215903_create_parc_info_cameras_ip_table	1
35	2026_05_15_000001_create_parc_info_contacts_table	1
36	2026_05_15_000002_create_parc_info_fournisseurs_table	1
37	2026_05_15_000003_create_parc_info_contrats_maintenances_table	1
38	2026_05_16_200000_create_parc_info_types_consommables_table	1
39	2026_05_16_200001_create_parc_info_consommables_table	1
40	2026_05_16_200002_create_parc_info_mouvements_consommables_table	1
41	2026_05_16_200003_create_parc_info_affectations_consommables_table	1
42	2026_05_16_210000_create_parc_info_types_licences_table	1
43	2026_05_16_210001_create_parc_info_editeurs_table	1
44	2026_05_16_210002_create_parc_info_logiciels_table	1
45	2026_05_16_210003_create_parc_info_licences_table	1
46	2026_05_16_210004_create_parc_info_affectations_licences_table	1
47	2026_05_16_210005_create_parc_info_documents_licences_table	1
48	2026_05_17_024521_add_fournisseur_id_to_parc_info_contacts_table	1
49	2026_05_18_164502_add_network_fields_to_parc_info_scanners_table	1
50	2026_05_19_003701_add_brassage_fields_to_parc_info_infrastructures	1
51	2026_05_22_000001_separate_serveurs_and_serveurs_virtuels_tables	2
52	2026_06_12_213700_add_address_fields_to_fournisseurs_table	3
53	2026_06_12_224801_add_service_and_unite_to_mouvements_consommables_table	4
54	2026_06_15_204112_add_ref_bordereau_to_parc_info_equipements_table	5
55	2026_06_15_211444_add_org_fields_to_parc_info_equipements_table	6
\.


--
-- Data for Name: model_has_permissions; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.model_has_permissions (permission_id, model_type, model_id) FROM stdin;
138	Modules\\Core\\Models\\User	22
\.


--
-- Data for Name: model_has_roles; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.model_has_roles (role_id, model_type, model_id) FROM stdin;
1	Modules\\Core\\Models\\User	3
1	Modules\\Core\\Models\\User	22
2	Modules\\Core\\Models\\User	22
\.


--
-- Data for Name: modules; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.modules (id, name, slug, description, version, is_active, is_required, dependencies, config, icon, sort_order, installed_at, activated_at, created_at, updated_at, deleted_at) FROM stdin;
1	Core	core	Module principal de Keystone gérant l'authentification, les utilisateurs, les rôles, les permissions et la gestion des modules. Il inclura prochainement une table 'configs' pour la gestion centralisée des configurations système.	1.0.0	t	f	[]	\N	fas fa-cogs	0	2026-05-21 19:30:12	\N	2026-05-21 19:30:12	2026-05-21 19:30:12	\N
2	Grh	grh		1.0.0	t	f	[]	\N	fas fa-cube	0	2026-05-21 19:30:12	\N	2026-05-21 19:30:12	2026-05-21 19:30:12	\N
3	Organisation	organisation		1.0.0	t	f	[]	\N	fas fa-cube	0	2026-05-21 19:30:12	\N	2026-05-21 19:30:12	2026-05-21 19:30:12	\N
4	ParcInfo	parcinfo		1.0.0	t	f	[]	\N	fas fa-cube	0	2026-05-21 19:30:12	\N	2026-05-21 19:30:12	2026-05-21 19:30:12	\N
\.


--
-- Data for Name: organisation_batiments; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.organisation_batiments (id, site_id, code, libelle, description, nombre_etages, actif, created_at, updated_at) FROM stdin;
1	1	BAT-ADMIN	Bâtiment Administratif	Direction générale et services d’appui	2	t	2026-05-21 19:38:57	2026-05-21 19:38:57
2	1	BAT-CHIR	Bâtiment Chirurgie	Blocs opératoires et hospitalisation chirurgie	2	t	2026-05-21 19:38:57	2026-05-21 19:38:57
3	1	BAT-MED	Bâtiment Médecine	Services de médecine interne et spécialités	1	t	2026-05-21 19:38:58	2026-05-21 19:38:58
4	1	BAT-DSI	Bâtiment DSI	Services informatiques	1	t	2026-05-21 19:38:58	2026-05-21 19:38:58
5	1	BAT-DG	DIirection Generale	\N	\N	t	2026-06-16 09:55:56	2026-06-16 09:55:56
\.


--
-- Data for Name: organisation_directions; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.organisation_directions (id, site_id, code, libelle, responsable_id, description, actif, created_at, updated_at) FROM stdin;
1	1	DAF	Direction de l’Administration et des Finances	\N	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
2	1	DSMT	Direction des Services Médicaux et Techniques	\N	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
3	1	DSIO	Direction des Soins Infirmiers et Obstétricaux	\N	Directrice actuelle : Madame Da Atian Elise depuis le 20 mai 2025	t	2026-05-21 19:38:57	2026-05-21 19:38:57
4	1	DRH	Direction des Ressources Humaines	\N	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
5	1	DQ	Direction de la Qualité	\N	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
6	1	DPHUC	Direction de la Prospective Hospitalo-Universitaire et de la Coopération	\N	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
7	1	DSGL	Direction des Services Généraux et de la Logistique	\N	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
8	1	DMP	Direction des Marchés Publics	\N	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
9	1	DCI	Direction du Contrôle Interne	\N	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
10	1	AC	Agence Comptable	\N	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
11	1	DSI	Direction des Services Informatiques	\N	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
12	1	DCMP	Direction du contrôle des marchés publics et des engagements financiers	\N	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
13	1	DG	Direction Générale	\N	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
\.


--
-- Data for Name: organisation_etages; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.organisation_etages (id, batiment_id, numero, libelle, actif, created_at, updated_at) FROM stdin;
1	1	0	Rez-de-chaussée	t	2026-05-21 19:38:57	2026-05-21 19:38:57
2	1	1	1er Étage	t	2026-05-21 19:38:57	2026-05-21 19:38:57
3	2	0	Rez-de-chaussée	t	2026-05-21 19:38:57	2026-05-21 19:38:57
4	2	1	1er Étage	t	2026-05-21 19:38:57	2026-05-21 19:38:57
5	3	0	Rez-de-chaussée	t	2026-05-21 19:38:58	2026-05-21 19:38:58
6	4	0	Rez-de-chaussée	t	2026-05-21 19:38:58	2026-05-21 19:38:58
7	5	1	Rez de chausse	t	2026-06-16 09:56:39	2026-06-16 09:56:39
\.


--
-- Data for Name: organisation_locaux; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.organisation_locaux (id, etage_id, code, libelle, type_local, superficie_m2, actif, created_at, updated_at) FROM stdin;
1	1	ADM-RDC-01	Accueil	salle_attente	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
2	1	ADM-RDC-02	Bureau Courrier	bureau	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
3	2	ADM-E1-01	Bureau DG	bureau	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
4	2	ADM-E1-02	Salle de réunion DG	bureau	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
5	3	CHIR-RDC-01	Urgences chirurgicales	salle_soins	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
6	4	CHIR-E1-01	Bloc opératoire 1	salle_soins	\N	t	2026-05-21 19:38:58	2026-05-21 19:38:58
7	5	MED-RDC-01	Consultations externes	salle_soins	\N	t	2026-05-21 19:38:58	2026-05-21 19:38:58
8	6	DSI-RDC-01	Salle Serveur	magasin	\N	t	2026-05-21 19:38:58	2026-05-21 19:38:58
9	6	DSI-RDC-02	Open Space Maintenance	bureau	\N	t	2026-05-21 19:38:58	2026-05-21 19:38:58
10	6	BS-00001	Bureau DSI	bureau	\N	t	2026-06-16 09:57:58	2026-06-16 09:57:58
\.


--
-- Data for Name: organisation_postes_travail; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.organisation_postes_travail (id, code, libelle, description, direction_id, service_id, unite_id, local_id, dossier_employe_id, statut, actif, created_at, updated_at, niveau_rattachement) FROM stdin;
1	POST-DSI-001	Poste Chef DSI	\N	11	19	\N	9	\N	actif	t	2026-05-21 19:38:58	2026-05-21 19:38:58	\N
2	POST-DSI-002	Poste Technicien Reseau 1	\N	11	21	\N	9	\N	actif	t	2026-05-21 19:38:58	2026-05-21 19:38:58	\N
3	POST-DAF-001	Poste Comptabilité 1	\N	1	7	\N	2	\N	actif	t	2026-05-21 19:38:58	2026-05-21 19:38:58	\N
4	POST-DG-001	Poste Secrétariat DG	\N	13	1	\N	3	\N	actif	t	2026-05-21 19:38:58	2026-05-21 19:38:58	\N
5	POST-URG-001	Poste Accueil Urgences	\N	2	39	1	5	\N	actif	t	2026-05-21 19:38:58	2026-05-21 19:38:58	\N
\.


--
-- Data for Name: organisation_services; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.organisation_services (id, direction_id, site_id, code, libelle, chef_service_id, actif, created_at, updated_at, type_service) FROM stdin;
1	13	1	SP-DG	Secrétariat particulier	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57	administratif
2	13	1	SPUB	Service de santé publique	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57	administratif
3	13	1	CRP	Service de la communication et des relations publiques	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57	administratif
4	13	1	SPIH	Service de planification et d’information hospitalière	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57	administratif
5	13	1	SCAD	Service central des archives et de la documentation	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57	administratif
6	13	1	REGIES	Régies d’avances et de recettes	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57	administratif
7	1	1	BUDGET	Service du budget	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57	administratif
8	1	1	GAP	Service de la gestion administrative des patients	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57	administratif
9	1	1	ACHATS	Service des achats	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57	administratif
10	4	1	GASP	Service de la gestion administrative et salariale du personnel	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57	administratif
11	4	1	RFP	Service du recrutement et de la formation professionnelle	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57	administratif
12	4	1	SOCIAL	Service des œuvres sociales	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57	administratif
13	7	1	PL	Le service du patrimoine et de la logistique	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57	administratif
14	7	1	TM	Le service des travaux et de la maintenance	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57	administratif
15	7	1	HOTEL	Le service de l’hôtellerie	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57	administratif
16	8	1	SMFSC	Le service des marchés de fournitures et services courants	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57	administratif
17	8	1	SMTEPI	Le service des marchés de travaux, équipements et prestations intellectuelles	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57	administratif
18	8	1	SSEMP	Le service de suivi de l’exécution des marchés publics	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57	administratif
19	11	1	MNT-INFO	Le service de maintenance informatique	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57	administratif
20	11	1	GEST-APP	Le service de gestion des applications informatiques	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57	administratif
21	11	1	RESEAUX	Le service de réseaux et télécommunications	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57	administratif
22	5	1	SNPQ	Le service de la normalisation et de la promotion de la qualité	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57	administratif
23	5	1	SEAC	Le service de l’évaluation et de l’amélioration continue	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57	administratif
24	5	1	SHSSP	Le service de l’hygiène hospitalière et de la sécurité des patients	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57	administratif
25	6	1	PHU	Le service de la prospective hospitalo-universitaire	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57	administratif
26	6	1	COOP	Le service de la coopération	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57	administratif
27	9	1	CTRL-GEST	Le service du contrôle de gestion	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57	administratif
28	9	1	AUDIT-INT	Le service de l’audit interne	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57	administratif
29	2	1	AFF-MED	Le service des affaires médicales	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57	administratif
30	2	1	AFF-MT	Le service des affaires médicotechniques	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57	administratif
31	2	1	INFO-MED	Le service de l’information médicale	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57	administratif
32	2	1	RECH-INV	Le service de la recherche et de l’innovation	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57	administratif
33	2	1	SOCIAL-MED	Le service social médical	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57	administratif
34	3	1	MUS	Le service de management des unités de soins	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57	administratif
35	3	1	ERSIO	Le service de l’évaluation et de la recherche en soins infirmiers et obstétricaux	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57	administratif
36	10	1	RECETTES	Le service de recettes	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57	administratif
37	10	1	DEPENSES	Le service de dépenses	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57	administratif
38	10	1	COMPTA	Le service de la comptabilité	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57	administratif
39	2	1	SERV-CLINIQUE	Service des Unités Cliniques	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57	clinique
\.


--
-- Data for Name: organisation_sites; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.organisation_sites (id, code, libelle, description, adresse, actif, created_at, updated_at) FROM stdin;
1	SITE-PRINCIPAL	Site Principal	Campus historique du CHU-YO	\N	t	2026-05-21 19:20:50	2026-05-21 19:20:50
2	SITE-GERIATRIE	Site Gériatrie	Centre spécialisé en gériatrie	\N	t	2026-05-21 19:20:50	2026-05-21 19:20:50
\.


--
-- Data for Name: organisation_unites; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.organisation_unites (id, service_id, site_id, code, libelle, major_id, actif, created_at, updated_at) FROM stdin;
1	39	1	URG	Urgences Médicales	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
2	39	1	PNEU	Pneumologie	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
3	39	1	HGE	Hépato-gastro-entérologie	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
4	39	1	NEPH	Néphrologie	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
5	39	1	CARD	Cardiologie	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
6	39	1	MED-INT	Médecine Interne	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
7	39	1	MAL-INF	Maladies Infectieuses	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
8	39	1	NEURO	Neurologie	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
9	39	1	PSY	Psychiatrie	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
10	39	1	DERMATO	Dermatologie vénérologie	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
11	39	1	HEMATO	Hématologie clinique	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
12	39	1	RHUMATO	Rhumatologie	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
13	39	1	ACUP	Acupuncture	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
14	39	1	MPR	Médecine physique et réadaptation	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
15	39	1	CHIR-GEN	Chirurgie Générale et Digestive	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
16	39	1	ORTHO-TRAU	Orthopédie-Traumatologie	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
17	39	1	ORL	Oto-rhino-laryngologie (ORL)	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
18	39	1	OPHTA	Ophtalmologie	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
19	39	1	NEURO-CHIR	Neurochirurgie	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
20	39	1	URO-ANDRO	Urologie-andrologie	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
21	39	1	GYNECO-OBST	Gynécologie et Obstétrique	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
22	39	1	ONCO	Oncologie	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
23	39	1	PEDIATRIE	Pédiatrie	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
24	39	1	AR	Anesthésie et réanimation	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
25	39	1	CHIR-DENT	Chirurgie dentaire	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
26	39	1	STOMATO	Stomatologie et chirurgie maxillo-faciale	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
27	39	1	SANTE-PUB	Santé publique	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
28	39	1	MED-TRAVAIL	Médecine du travail	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
29	39	1	IMAGERIE	Imagerie médicale et radiodiagnostic	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
30	39	1	PHYSIO	Physiologie, Exploration fonctionnelle et Médecine du sport	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
31	39	1	LABO	Laboratoire	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
32	39	1	ANAPATH	Anatomocytologie pathologique et médecine légale	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
33	39	1	PHARMACIE	Pharmacie hospitalière	\N	t	2026-05-21 19:38:57	2026-05-21 19:38:57
34	34	1	UNT-URG-1	Urgences Médicales	1	t	2026-06-16 09:54:33	2026-06-16 09:54:33
\.


--
-- Data for Name: parc_info_affectation_equipements; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.parc_info_affectation_equipements (id, code, date_debut, date_fin, equipement_id, statut, type_affectation, type_cible, dossier_employe_id, poste_travail_id, local_id, niveau_rattachement, direction_id, service_id, unite_id, created_at, updated_at) FROM stdin;
1	AFF-6A0F5F6EB1D48	2026-05-21	2026-05-21	1	f	PERMANENTE	LOCAL	\N	\N	2	\N	\N	\N	\N	2026-05-21 19:39:26	2026-05-21 19:39:37
2	AFF-6A105E82D6BED	2026-05-22	\N	3	t	PERMANENTE	POSTE	\N	\N	3	\N	\N	\N	\N	2026-05-22 13:47:46	2026-05-22 13:47:46
3	AFF-6A105EF053A0D	2026-05-22	\N	4	t	PERMANENTE	LOCAL	\N	\N	2	\N	\N	\N	\N	2026-05-22 13:49:36	2026-05-22 13:49:36
4	AFF-6A1D776A2DA5D	2026-06-01	2026-06-01	9	f	PERMANENTE	LOCAL	\N	\N	3	\N	\N	\N	\N	2026-06-01 12:13:30	2026-06-01 12:13:51
5	AFF-6A1D77E428134	2026-06-01	2026-06-01	9	f	PERMANENTE	LOCAL	\N	\N	1	\N	\N	\N	\N	2026-06-01 12:15:32	2026-06-01 12:17:25
6	AFF-6A1D7CA485AEA	2026-06-01	\N	9	t	PERMANENTE	LOCAL	\N	\N	4	\N	\N	\N	\N	2026-06-01 12:35:48	2026-06-01 12:35:48
7	AFF-6A2009746FBC8	2026-06-03	\N	12	t	PERMANENTE	EMPLOYE	2	\N	\N	direction	3	\N	\N	2026-06-03 11:01:08	2026-06-03 11:01:08
8	AFF-6A200A0EB55ED	2026-06-03	\N	13	t	PERMANENTE	EMPLOYE	2	\N	\N	direction	3	\N	\N	2026-06-03 11:03:42	2026-06-03 11:03:42
9	AFF-6A200DD75F9B8	2026-06-03	\N	14	t	PERMANENTE	LOCAL	\N	\N	2	\N	\N	\N	\N	2026-06-03 11:19:51	2026-06-03 11:19:51
13	AFF-6A2C3B613C7A3	2026-06-12	\N	21	t	PERMANENTE	EMPLOYE	2	\N	\N	direction	3	\N	\N	2026-06-12 17:01:21	2026-06-12 17:01:21
14	AFF-6A3121134E858	2026-06-16	2026-06-16	20	f	PERMANENTE	EMPLOYE	1	\N	\N	direction	13	\N	\N	2026-06-16 10:10:27	2026-06-16 10:11:07
15	AFF-6A31216D858A5	2026-06-16	2026-06-16	20	f	PERMANENTE	POSTE	\N	1	\N	\N	11	19	\N	2026-06-16 10:11:57	2026-06-16 10:29:30
16	AFF-6A3125E31E9ED	2026-06-16	\N	20	t	PERMANENTE	POSTE	\N	1	\N	\N	11	19	\N	2026-06-16 10:30:59	2026-06-16 10:30:59
\.


--
-- Data for Name: parc_info_affectations_consommables; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.parc_info_affectations_consommables (id, consommable_id, equipement_id, quantite_fournie, date_affectation, date_remplacement_prochain_prevu, cycle_remplacement_jours, notes, created_at, updated_at) FROM stdin;
2	1	9	1	2026-06-12	\N	\N	\N	2026-06-12 23:20:35	2026-06-12 23:20:35
\.


--
-- Data for Name: parc_info_affectations_licences; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.parc_info_affectations_licences (id, licence_id, equipement_id, employe_id, type_affectation, date_affectation, date_fin_affectation, actif, notes, created_at, updated_at) FROM stdin;
2	1	\N	\N	device	2026-06-12	\N	t	\N	2026-06-12 22:17:11	2026-06-12 22:17:11
3	1	20	\N	device	2026-06-19	\N	t	\N	2026-06-19 17:53:28	2026-06-19 17:53:28
1	1	20	\N	device	2026-06-12	2026-06-19	f	\N	2026-06-12 22:08:21	2026-06-19 17:53:45
\.


--
-- Data for Name: parc_info_cameras_ip; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.parc_info_cameras_ip (equipement_id, adresse_ip, adresse_mac, resolution, type_camera, emplacement) FROM stdin;
\.


--
-- Data for Name: parc_info_consommables; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.parc_info_consommables (id, code, nom, type_consommable_id, marque_id, modele_reference, compatible_equipements, fournisseur_principal_id, cout_unitaire, quantite_stock_actuel, quantite_stock_min, quantite_stock_max, stock_reserve_maintenance, date_dernier_approvisionnement, est_actif, notes, created_at, updated_at) FROM stdin;
1	S0001	SOURIS USB	1	6	\N	\N	2	100000.00	2	5	20	0	2026-06-12	t	\N	2026-06-12 22:38:21	2026-06-16 08:45:09
\.


--
-- Data for Name: parc_info_contacts; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.parc_info_contacts (id, nom, prenom, email, telephone, fonction, est_actif, created_at, updated_at, fournisseur_id) FROM stdin;
1	Ibrahim	GNINASSE	ibrahim.gninasse@gmail.com	\N	ddfg	t	2026-06-12 21:42:38	2026-06-12 21:42:38	2
\.


--
-- Data for Name: parc_info_contrats_maintenances; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.parc_info_contrats_maintenances (id, reference, nom, fournisseur_id, date_debut, date_fin, cout, est_actif, notes, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: parc_info_documents_licences; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.parc_info_documents_licences (id, licence_id, type, nom_fichier, chemin_stockage, date_document, description, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: parc_info_editeurs; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.parc_info_editeurs (id, code, nom, logo_url, site_web, email_support, telephone_support, est_actif, created_at, updated_at) FROM stdin;
2	DDD853	ddd	\N	\N	\N	\N	t	2026-06-12 18:26:50	2026-06-12 18:26:50
\.


--
-- Data for Name: parc_info_equipements; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.parc_info_equipements (id, code_inventaire, numero_serie, marque_id, modele, date_acquisition, date_mise_en_service, valeur_achat, duree_vie_probable, date_fin_garantie, statut, etat, tags, created_at, updated_at, ref_bordereau, direction_id, service_id, unite_id) FROM stdin;
1	NET-2026-0001	derf	1	fg	\N	\N	\N	\N	\N	en_stock	bon	\N	2026-05-21 19:34:59	2026-05-21 19:39:37	\N	\N	\N	\N
2	NET-2026-0002	sdfghjk	1	fvgb	\N	\N	\N	\N	\N	en_stock	bon	\N	2026-05-21 21:22:34	2026-05-21 21:22:34	\N	\N	\N	\N
8	SRV-2026-0008	S/N 234-345	1	PowerEdge	\N	\N	\N	\N	\N	en_stock	bon	\N	2026-06-01 10:47:08	2026-06-01 10:47:08	\N	\N	\N	\N
10	VM-2026-0010	dfg-frtg-gh-gtyh	\N	Machine Virtuelle	\N	\N	\N	\N	\N	en_service	bon	\N	2026-06-03 10:35:05	2026-06-03 10:35:05	\N	\N	\N	\N
16	VM-2026-0016	vmdfg	\N	Machine Virtuelle	\N	\N	\N	\N	\N	en_service	bon	\N	2026-06-12 15:42:15	2026-06-12 15:42:15	\N	\N	\N	\N
17	INV-2026-0017	S/N3drf@#$%^	1	Optiplex 4000	\N	\N	\N	\N	\N	en_stock	bon	\N	2026-06-12 16:43:01	2026-06-12 16:43:01	\N	\N	\N	\N
3	SRV-2026-0003	dfg-frtg-gh-derf	1	Optiplex-dfg	\N	\N	\N	\N	\N	en_service	bon	\N	2026-05-22 13:47:46	2026-05-22 13:47:46	\N	\N	\N	\N
4	VM-2026-0004	edr	1	ffgh	\N	\N	\N	\N	\N	en_service	bon	\N	2026-05-22 13:49:36	2026-05-22 13:49:36	\N	\N	\N	\N
9	SRV-2026-0009	dfg-frtg-gh-de	2	PowerEdge3	2026-06-03	\N	\N	\N	\N	en_service	bon	\N	2026-06-01 11:59:59	2026-06-03 10:14:32	\N	\N	\N	\N
12	MOB-2026-0011	seedcvfg	3	edfrvc	\N	\N	\N	\N	\N	en_service	bon	\N	2026-06-03 11:00:16	2026-06-03 11:01:08	\N	3	\N	\N
13	MOB-2026-0013	sedrf-frtg	1	derf-fred	\N	\N	\N	\N	\N	en_service	bon	\N	2026-06-03 11:03:42	2026-06-03 11:03:42	\N	3	\N	\N
14	NET-2026-0014	derftg	1	gtyh	\N	\N	\N	\N	\N	en_service	bon	\N	2026-06-03 11:19:51	2026-06-03 11:19:51	\N	\N	\N	\N
21	MOB-2026-0021	S/N R58M30XYZ12	5	Galaxy A54	\N	\N	\N	\N	\N	en_service	bon	\N	2026-06-12 17:00:25	2026-06-12 17:01:21	\N	3	\N	\N
20	INV-2026-0020	S/N 4G7B9X2	4	OptiPlex 7000	\N	\N	\N	\N	\N	en_service	bon	\N	2026-06-12 16:52:19	2026-06-16 10:34:09	\N	11	19	\N
\.


--
-- Data for Name: parc_info_equipements_reseaux; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.parc_info_equipements_reseaux (equipement_id, type_reseau_id, nb_ports, vitesse_max_mbps, est_poe, version_firmware, u_position_depart, u_position_fin, vlan_management, adresse_ip, masque_sous_reseau, passerelle, communaute_snmp, est_manageable) FROM stdin;
1	1	\N	\N	f	\N	\N	\N	\N	\N	\N	\N	\N	t
2	2	\N	\N	f	\N	\N	\N	\N	\N	\N	\N	\N	t
14	3	\N	\N	f	\N	\N	\N	\N	192.2.2.10	\N	\N	\N	t
\.


--
-- Data for Name: parc_info_fournisseurs; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.parc_info_fournisseurs (id, code, nom, type, email, telephone, adresse, contact_principal_id, conditions_paiement, delai_livraison, fiabilite_score, est_actif, created_at, updated_at, code_postal, ville, pays) FROM stdin;
2	DSIF-AR	GNINASSE Ibrahim	Revendeur	ibrahim.gninasse@gmail.com	\N	KATRE yare	\N	\N	\N	100	t	2026-06-12 18:26:03	2026-06-12 18:26:03	\N	\N	\N
\.


--
-- Data for Name: parc_info_historique_changements; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.parc_info_historique_changements (id, equipement_id, date_changement, utilisateur_id, type_changement, ancien_statut, nouveau_statut, ancien_etat, nouvel_etat, motif, reference_document, created_at, updated_at) FROM stdin;
1	1	2026-05-21 19:39:26	3	STATUT	en_stock	en_service	\N	\N	Mise en service automatique suite à affectation	\N	2026-05-21 19:39:26	2026-05-21 19:39:26
2	1	2026-05-21 19:39:26	3	AFFECTATION	en_stock	en_service	\N	\N	Nouvelle affectation	\N	2026-05-21 19:39:26	2026-05-21 19:39:26
3	1	2026-05-21 19:39:37	3	AFFECTATION	\N	\N	\N	\N	Désaffectation : dfgh	\N	2026-05-21 19:39:37	2026-05-21 19:39:37
4	1	2026-05-21 19:39:37	3	STATUT	en_service	en_stock	\N	\N	Mise en stock automatique suite à désaffectation	\N	2026-05-21 19:39:37	2026-05-21 19:39:37
6	9	2026-06-01 12:13:30	3	STATUT	en_stock	en_service	\N	\N	Mise en service automatique suite à affectation	\N	2026-06-01 12:13:30	2026-06-01 12:13:30
7	9	2026-06-01 12:13:30	3	AFFECTATION	en_stock	en_service	\N	\N	Nouvel emplacement défini	\N	2026-06-01 12:13:30	2026-06-01 12:13:30
8	9	2026-06-01 12:13:51	3	AFFECTATION	\N	\N	\N	\N	Désaffectation : Motif	\N	2026-06-01 12:13:51	2026-06-01 12:13:51
9	9	2026-06-01 12:13:51	3	STATUT	en_service	en_stock	\N	\N	Mise en stock automatique suite à désaffectation	\N	2026-06-01 12:13:51	2026-06-01 12:13:51
10	9	2026-06-01 12:15:32	3	STATUT	en_stock	en_service	\N	\N	Mise en service automatique suite à affectation	\N	2026-06-01 12:15:32	2026-06-01 12:15:32
11	9	2026-06-01 12:15:32	3	AFFECTATION	en_stock	en_service	\N	\N	Nouvel emplacement défini	\N	2026-06-01 12:15:32	2026-06-01 12:15:32
12	9	2026-06-01 12:17:25	3	AFFECTATION	\N	\N	\N	\N	Désaffectation : derf	\N	2026-06-01 12:17:25	2026-06-01 12:17:25
13	9	2026-06-01 12:17:25	3	STATUT	en_service	en_stock	\N	\N	Mise en stock automatique suite à désaffectation	\N	2026-06-01 12:17:25	2026-06-01 12:17:25
14	9	2026-06-01 12:35:48	3	STATUT	en_stock	en_service	\N	\N	Mise en service automatique suite à affectation	\N	2026-06-01 12:35:48	2026-06-01 12:35:48
15	9	2026-06-01 12:35:48	3	AFFECTATION	en_stock	en_service	\N	\N	Nouvel emplacement défini	\N	2026-06-01 12:35:48	2026-06-01 12:35:48
17	12	2026-06-03 11:00:16	3	STATUT	\N	en_stock	\N	\N	Enregistrement initial de l'équipement mobile	\N	2026-06-03 11:00:16	2026-06-03 11:00:16
18	12	2026-06-03 11:01:08	3	STATUT	en_stock	en_service	\N	\N	Mise en service automatique suite à affectation	\N	2026-06-03 11:01:08	2026-06-03 11:01:08
19	12	2026-06-03 11:01:08	3	AFFECTATION	\N	\N	\N	\N	Nouvelle affectation via l'interface show	\N	2026-06-03 11:01:08	2026-06-03 11:01:08
20	13	2026-06-03 11:03:42	3	STATUT	\N	en_service	\N	\N	Enregistrement initial de l'équipement mobile	\N	2026-06-03 11:03:42	2026-06-03 11:03:42
23	21	2026-06-12 17:00:25	3	STATUT	\N	en_stock	\N	\N	Enregistrement initial de l'équipement mobile	\N	2026-06-12 17:00:25	2026-06-12 17:00:25
24	21	2026-06-12 17:01:21	3	STATUT	en_stock	en_service	\N	\N	Mise en service automatique suite à affectation	\N	2026-06-12 17:01:21	2026-06-12 17:01:21
25	21	2026-06-12 17:01:21	3	AFFECTATION	\N	\N	\N	\N	Nouvelle affectation via l'interface show	\N	2026-06-12 17:01:21	2026-06-12 17:01:21
26	20	2026-06-16 10:10:27	3	STATUT	en_stock	en_service	\N	\N	Mise en service automatique suite à affectation	\N	2026-06-16 10:10:27	2026-06-16 10:10:27
27	20	2026-06-16 10:10:27	3	AFFECTATION	en_stock	en_service	\N	\N	Nouvelle affectation	\N	2026-06-16 10:10:27	2026-06-16 10:10:27
28	20	2026-06-16 10:11:07	3	AFFECTATION	\N	\N	\N	\N	Désaffectation : MOTIF	\N	2026-06-16 10:11:07	2026-06-16 10:11:07
29	20	2026-06-16 10:11:07	3	STATUT	en_service	en_stock	\N	\N	Mise en stock automatique suite à désaffectation	\N	2026-06-16 10:11:07	2026-06-16 10:11:07
30	20	2026-06-16 10:11:57	3	STATUT	en_stock	en_service	\N	\N	Mise en service automatique suite à affectation	\N	2026-06-16 10:11:57	2026-06-16 10:11:57
31	20	2026-06-16 10:11:57	3	AFFECTATION	en_stock	en_service	\N	\N	Nouvelle affectation	\N	2026-06-16 10:11:57	2026-06-16 10:11:57
32	20	2026-06-16 10:29:30	3	AFFECTATION	\N	\N	\N	\N	Désaffectation : FRTGT	\N	2026-06-16 10:29:30	2026-06-16 10:29:30
33	20	2026-06-16 10:29:30	3	STATUT	en_service	en_stock	\N	\N	Mise en stock automatique suite à désaffectation	\N	2026-06-16 10:29:30	2026-06-16 10:29:30
34	20	2026-06-16 10:30:59	3	STATUT	en_stock	en_service	\N	\N	Mise en service automatique suite à affectation	\N	2026-06-16 10:30:59	2026-06-16 10:30:59
35	20	2026-06-16 10:30:59	3	AFFECTATION	en_stock	en_service	\N	\N	Nouvelle affectation	\N	2026-06-16 10:30:59	2026-06-16 10:30:59
36	20	2026-06-16 10:33:40	3	STATUT	en_service	en_reparation	\N	\N	FGTYH	\N	2026-06-16 10:33:40	2026-06-16 10:33:40
37	20	2026-06-16 10:34:09	3	STATUT	en_reparation	en_service	\N	\N	REDRTGT	\N	2026-06-16 10:34:09	2026-06-16 10:34:09
\.


--
-- Data for Name: parc_info_imprimantes; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.parc_info_imprimantes (equipement_id, type_imprimante_id, est_couleur, est_multifonction, fonctions, adresse_ip, snmp_community) FROM stdin;
\.


--
-- Data for Name: parc_info_infrastructures; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.parc_info_infrastructures (equipement_id, type_infra_id, puissance_va, autonomie_minutes, date_dernier_remplacement_batterie, nb_prises_pdu, u_capacite_totale, est_redondant, nb_ports, categorie_cable, type_connecteur, u_taille) FROM stdin;
\.


--
-- Data for Name: parc_info_licences; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.parc_info_licences (id, logiciel_id, cle_licence, numero_contrat, contrat_maintenance_id, type_activation, modele_licencing, nombre_postes_accordes, nombre_postes_utilises, date_acquisition, date_activation, date_expiration, date_renouvellement_prochain, cout_unitaire, cout_total, devise, fournisseur_id, contact_support_id, statut, conditions_utilisation, actif, notes, created_at, updated_at) FROM stdin;
1	2	hjui55555	cfgghh	\N	volume	device	6	2	2026-06-12	\N	2027-03-13	\N	\N	\N	EUR	2	\N	actif	\N	t	\N	2026-06-12 22:07:37	2026-06-19 17:53:45
\.


--
-- Data for Name: parc_info_logiciels; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.parc_info_logiciels (id, code, nom, description, type_licence_id, editeur_id, categorie, est_actif, notes, created_at, updated_at) FROM stdin;
2	DSIF-AR5ff	GNINASSE Ibrahim	\N	2	2	bureautique	t	\N	2026-06-12 21:26:36	2026-06-12 21:26:36
\.


--
-- Data for Name: parc_info_marques; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.parc_info_marques (id, libelle, created_at, updated_at) FROM stdin;
1	fg	2026-05-21 19:34:53	2026-05-21 19:34:53
2	hp	2026-06-01 11:59:03	2026-06-01 11:59:03
3	fr	2026-06-03 10:58:59	2026-06-03 10:58:59
4	Dell	2026-06-12 16:49:19	2026-06-12 16:49:19
5	Samsung	2026-06-12 16:58:49	2026-06-12 16:58:49
6	dd	2026-06-12 17:05:07	2026-06-12 17:05:07
7	Cisco	2026-06-12 18:28:49	2026-06-12 18:28:49
\.


--
-- Data for Name: parc_info_mobiles; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.parc_info_mobiles (equipement_id, type_mobile_id, imei_1, imei_2, num_tel_associe, version_os, statut_mdm, capacite_batterie_mah, etat_ecran, a_coque_protection) FROM stdin;
12	1	\N	\N	\N	sedf	\N	\N	Intact	t
13	1	\N	\N	\N	cfg	\N	\N	Intact	t
21	2	354829104857291	\N	+226 70 12 34 56	Android 13	\N	\N	Intact	t
\.


--
-- Data for Name: parc_info_mouvements_consommables; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.parc_info_mouvements_consommables (id, consommable_id, type_mouvement, quantite, prix_unitaire, date_mouvement, reference_commande, utilisateur_id, equipement_id, employe_id, raison, notes, created_at, updated_at, service_id, unite_id) FROM stdin;
1	1	Achat	3	100000.00	2026-06-12 22:39:09	\N	3	\N	\N	\N	\N	2026-06-12 22:39:09	2026-06-12 22:39:09	\N	\N
2	1	Achat	4	100000.00	2026-06-12 22:40:56	\N	3	\N	\N	\N	\N	2026-06-12 22:40:56	2026-06-12 22:40:56	\N	\N
3	1	Consommation	1	\N	2026-06-12 23:15:19	\N	3	\N	\N	Remplacement standard	testtest, test	2026-06-12 23:15:19	2026-06-12 23:15:19	\N	\N
5	1	Consommation	1	\N	2026-06-12 23:16:08	\N	3	\N	1	Remplacement standard	\N	2026-06-12 23:16:08	2026-06-12 23:16:08	\N	\N
6	1	Consommation	1	\N	2026-06-12 23:16:41	\N	3	\N	\N	Remplacement standard	\N	2026-06-12 23:16:41	2026-06-12 23:16:41	37	\N
7	1	Consommation	1	\N	2026-06-12 23:20:35	\N	3	9	\N	Remplacement standard	\N	2026-06-12 23:20:35	2026-06-12 23:20:35	\N	\N
4	1	Consommation	1	\N	2026-06-12 23:15:46	\N	3	\N	\N	Remplacement standard	\N	2026-06-12 23:15:46	2026-06-12 23:15:46	\N	\N
\.


--
-- Data for Name: parc_info_ordinateurs; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.parc_info_ordinateurs (equipement_id, type_pc, ram_type_id, ram_capacite_go, cpu_type_id, processeur_model, disque_type_id, stockage_capacite_go, os_type_id, licence_windows_type, licence_windows_cle, licence_office_type, licence_office_cle, support_tpm2, support_secure_boot, bios_version, uefi_version, nom_hote, compte_admin_local, domaine_workgroup, adresse_mac_wifi, adresse_mac_ethernet, cycle_batterie) FROM stdin;
17	Fixe	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	f	f	\N	\N	\N	\N	\N	\N	\N	\N
20	Fixe	3	\N	3	Intel Core i5-12500	2	\N	4	\N	\N	\N	\N	f	f	\N	\N	PC-MED-042	admin_it	\N	\N	\N	\N
\.


--
-- Data for Name: parc_info_scanners; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.parc_info_scanners (equipement_id, resolution_dpi_max, format_max, est_recto_verso, a_chargeur_auto, type_capteur, adresse_ip, interface_connexion) FROM stdin;
\.


--
-- Data for Name: parc_info_serveurs; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.parc_info_serveurs (equipement_id, role_serveur, ram_type_id, ram_capacite_go, cpu_type_id, nb_processeurs, nb_coeurs_total, disque_type_id, stockage_capacite_go, os_type_id, nom_hote, domaine, adresse_ip, adresse_mac, hyperviseur, u_position_depart, u_position_fin) FROM stdin;
3	AD	\N	\N	\N	\N	\N	\N	\N	1	\N	\N	\N	\N	\N	1	\N
8	Web	\N	-1	1	12	12	\N	\N	2	svr-	\N	192.2.2.10	\N	ESXI	1	1
9	Web 2	2	\N	2	\N	\N	\N	\N	3	derf-der	\N	\N	\N	ESXI2	\N	\N
\.


--
-- Data for Name: parc_info_serveurs_virtuels; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.parc_info_serveurs_virtuels (equipement_id, role_serveur, ram_type_id, ram_capacite_go, cpu_type_id, nb_processeurs, nb_coeurs_total, disque_type_id, stockage_capacite_go, os_type_id, nom_hote, domaine, adresse_ip, adresse_mac, hyperviseur, serveur_hote_id) FROM stdin;
4	e	\N	\N	\N	\N	\N	\N	\N	1	\N	\N	\N	\N	df	3
10	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	3
16	\N	\N	\N	\N	\N	\N	\N	\N	2	\N	\N	\N	\N	\N	3
\.


--
-- Data for Name: parc_info_telephones; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.parc_info_telephones (equipement_id, est_ip, extension, protocole, adresse_mac_ethernet, adresse_ip, modele_expansion_count) FROM stdin;
\.


--
-- Data for Name: parc_info_types_consommables; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.parc_info_types_consommables (id, code, nom, categorie, sous_categorie, unite_stock, seul_reapprovisionnement, duree_conservation_jours, description, created_at, updated_at) FROM stdin;
1	TYPE-CONS-GNI570	GNINASSE Ibrahim	Impression	\N	df	5	\N	\N	2026-06-12 22:37:53	2026-06-12 22:37:53
\.


--
-- Data for Name: parc_info_types_cpus; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.parc_info_types_cpus (id, libelle, created_at, updated_at) FROM stdin;
1	df	2026-05-22 17:23:09	2026-05-22 17:23:09
2	intel	2026-06-01 11:59:32	2026-06-01 11:59:32
3	Intel Core i5	2026-06-12 16:49:47	2026-06-12 16:49:47
\.


--
-- Data for Name: parc_info_types_disques; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.parc_info_types_disques (id, libelle, created_at, updated_at) FROM stdin;
1	derf	2026-05-22 17:23:19	2026-05-22 17:23:19
2	SSD NVMe	2026-06-12 16:50:58	2026-06-12 16:50:58
\.


--
-- Data for Name: parc_info_types_imprimantes; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.parc_info_types_imprimantes (id, libelle, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: parc_info_types_infrastructures; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.parc_info_types_infrastructures (id, libelle, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: parc_info_types_licences; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.parc_info_types_licences (id, code, libelle, description, modeles, created_at, updated_at) FROM stdin;
2	L0001	Licence perpétuelle	Achat unique permettant une utilisation illimitée dans le temps	\N	2026-06-12 21:24:49	2026-06-16 07:39:48
3	L002	Licence par abonnement	Paiement récurrent (mensuel ou annuel) donnant accès aux dernières mises à jour et fonctionnalités durant la période de validité.	\N	2026-06-12 21:25:01	2026-06-16 07:40:57
4	L003	Licence flottante (ou concurrente)	Nombre défini de licences partagées entre plusieurs utilisateurs. Seule compte l'utilisation simultanée.	\N	2026-06-16 07:41:56	2026-06-16 07:41:56
5	L004	Licence par utilisateur / par poste	Liée à un individu spécifique ou installée sur une machine unique, quel que soit le nombre d'utilisateurs.	\N	2026-06-16 07:42:45	2026-06-16 07:42:45
6	L005	Logiciels libres et Open Source	Permettent une modification et une redistribution plus flexibles selon les conditions spécifiques de la licence	\N	2026-06-16 07:43:21	2026-06-16 07:43:21
\.


--
-- Data for Name: parc_info_types_mobiles; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.parc_info_types_mobiles (id, libelle, created_at, updated_at) FROM stdin;
1	frt	2026-05-22 17:24:08	2026-05-22 17:24:08
2	Smartphone	2026-06-12 16:59:18	2026-06-12 16:59:18
\.


--
-- Data for Name: parc_info_types_os; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.parc_info_types_os (id, libelle, created_at, updated_at) FROM stdin;
1	w11	2026-05-22 13:47:16	2026-05-22 13:47:16
2	derf	2026-05-22 17:23:24	2026-05-22 17:23:24
3	server11	2026-06-01 11:59:16	2026-06-01 11:59:16
4	Windows 10 Pro	2026-06-12 16:50:37	2026-06-12 16:50:37
\.


--
-- Data for Name: parc_info_types_rams; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.parc_info_types_rams (id, libelle, created_at, updated_at) FROM stdin;
1	der	2026-05-22 17:23:13	2026-05-22 17:23:13
2	ddr4	2026-06-01 11:59:45	2026-06-01 11:59:45
3	DDR4	2026-06-12 16:50:19	2026-06-12 16:50:19
\.


--
-- Data for Name: parc_info_types_reseaux; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.parc_info_types_reseaux (id, libelle, created_at, updated_at) FROM stdin;
1	Routeur	2026-05-21 19:34:59	2026-05-21 19:34:59
2	Pare-feu	2026-05-21 21:22:34	2026-05-21 21:22:34
3	Switch	2026-06-03 11:19:26	2026-06-03 11:19:26
\.


--
-- Data for Name: password_reset_tokens; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.password_reset_tokens (email, token, created_at) FROM stdin;
\.


--
-- Data for Name: permissions; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.permissions (id, name, guard_name, created_at, updated_at, label, module, category, description, "group", sort_order, is_visible) FROM stdin;
1	cores.dashboard.view	web	2026-05-21 19:30:12	2026-05-21 19:30:12	Voir le tableau de bord	core	view	Voir le tableau de bord	\N	0	t
2	cores.users.index	web	2026-05-21 19:30:12	2026-05-21 19:30:12	Voir la liste des utilisateurs	core	view	Voir la liste des utilisateurs	\N	0	t
3	cores.users.store	web	2026-05-21 19:30:12	2026-05-21 19:30:12	Créer un utilisateur	core	create	Créer un utilisateur	\N	0	t
4	cores.users.update	web	2026-05-21 19:30:12	2026-05-21 19:30:12	Modifier un utilisateur	core	edit	Modifier un utilisateur	\N	0	t
5	cores.users.destroy	web	2026-05-21 19:30:12	2026-05-21 19:30:12	Supprimer un utilisateur	core	delete	Supprimer un utilisateur	\N	0	t
6	cores.users.reset-password	web	2026-05-21 19:30:12	2026-05-21 19:30:12	Réinitialiser le mot de passe	core	other	Réinitialiser le mot de passe	\N	0	t
7	cores.users.toggle-status	web	2026-05-21 19:30:12	2026-05-21 19:30:12	Activer/Désactiver un utilisateur	core	toggle	Activer/Désactiver un utilisateur	\N	0	t
8	cores.roles.index	web	2026-05-21 19:30:12	2026-05-21 19:30:12	Voir la liste des rôles	core	view	Voir la liste des rôles	\N	0	t
9	cores.roles.store	web	2026-05-21 19:30:12	2026-05-21 19:30:12	Créer un rôle	core	create	Créer un rôle	\N	0	t
10	cores.roles.update	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Modifier un rôle	core	edit	Modifier un rôle	\N	0	t
11	cores.roles.destroy	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Supprimer un rôle	core	delete	Supprimer un rôle	\N	0	t
12	cores.permissions.index	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Voir la matrice des permissions	core	view	Voir la matrice des permissions	\N	0	t
13	cores.permissions.toggle	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Modifier les permissions	core	toggle	Modifier les permissions	\N	0	t
14	cores.permissions.sync	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Synchroniser les permissions	core	other	Synchroniser les permissions	\N	0	t
15	cores.modules.index	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Voir la liste des modules	core	view	Voir la liste des modules	\N	0	t
16	cores.modules.show	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Voir les détails d'un module	core	view	Voir les détails d'un module	\N	0	t
17	cores.modules.install	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Installer un module	core	install	Installer un module	\N	0	t
18	cores.modules.uninstall	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Désinstaller un module	core	uninstall	Désinstaller un module	\N	0	t
19	cores.modules.enable	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Activer un module	core	enable	Activer un module	\N	0	t
20	cores.modules.disable	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Désactiver un module	core	disable	Désactiver un module	\N	0	t
21	cores.modules.configure	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Configurer un module	core	configure	Configurer un module	\N	0	t
22	cores.activities.index	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Voir le journal des activités	core	view	Voir le journal des activités	\N	0	t
23	cores.activities.data	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Accéder aux données des activités	core	other	Accéder aux données des activités	\N	0	t
24	cores.activities.show	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Voir les détails d'une activité	core	view	Voir les détails d'une activité	\N	0	t
25	cores.activities.export	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Exporter l'historique	core	other	Exporter l'historique	\N	0	t
26	cores.activities.cleanup	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Nettoyer les anciennes activités	core	other	Nettoyer les anciennes activités	\N	0	t
27	grh.dashboard.view	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Voir le tableau de bord GRH	grh	view	Voir le tableau de bord GRH	\N	0	t
28	grh.employes.index	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Voir la liste des dossiers employés	grh	view	Voir la liste des dossiers employés	\N	0	t
29	grh.employes.store	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Créer un dossier employé	grh	create	Créer un dossier employé	\N	0	t
30	grh.employes.update	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Modifier un dossier employé	grh	edit	Modifier un dossier employé	\N	0	t
31	grh.employes.destroy	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Supprimer un dossier employé	grh	delete	Supprimer un dossier employé	\N	0	t
32	grh.employes.show	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Voir les détails d'un dossier employé	grh	view	Voir les détails d'un dossier employé	\N	0	t
33	grh.employes.export	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Exporter les dossiers employés	grh	other	Exporter les dossiers employés	\N	0	t
34	organisation.sites.index	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Voir la liste des sites	organisation	view	Voir la liste des sites	\N	0	t
35	organisation.sites.store	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Créer un site	organisation	create	Créer un site	\N	0	t
36	organisation.sites.update	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Modifier un site	organisation	edit	Modifier un site	\N	0	t
37	organisation.sites.destroy	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Supprimer un site	organisation	delete	Supprimer un site	\N	0	t
38	organisation.sites.toggle-status	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Activer/Désactiver un site	organisation	toggle	Activer/Désactiver un site	\N	0	t
39	organisation.directions.index	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Voir la liste des directions	organisation	view	Voir la liste des directions	\N	0	t
40	organisation.directions.store	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Créer une direction	organisation	create	Créer une direction	\N	0	t
41	organisation.directions.update	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Modifier une direction	organisation	edit	Modifier une direction	\N	0	t
42	organisation.directions.destroy	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Supprimer une direction	organisation	delete	Supprimer une direction	\N	0	t
43	organisation.directions.toggle-status	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Activer/Désactiver une direction	organisation	toggle	Activer/Désactiver une direction	\N	0	t
44	organisation.services.index	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Voir la liste des services	organisation	view	Voir la liste des services	\N	0	t
45	organisation.services.store	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Créer un service	organisation	create	Créer un service	\N	0	t
46	organisation.services.update	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Modifier un service	organisation	edit	Modifier un service	\N	0	t
47	organisation.services.destroy	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Supprimer un service	organisation	delete	Supprimer un service	\N	0	t
48	organisation.services.toggle-status	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Activer/Désactiver un service	organisation	toggle	Activer/Désactiver un service	\N	0	t
49	organisation.unites.index	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Voir la liste des unités cliniques	organisation	view	Voir la liste des unités cliniques	\N	0	t
50	organisation.unites.store	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Créer une unité clinique	organisation	create	Créer une unité clinique	\N	0	t
51	organisation.unites.update	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Modifier une unité clinique	organisation	edit	Modifier une unité clinique	\N	0	t
52	organisation.unites.destroy	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Supprimer une unité clinique	organisation	delete	Supprimer une unité clinique	\N	0	t
53	organisation.unites.toggle-status	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Activer/Désactiver une unité clinique	organisation	toggle	Activer/Désactiver une unité clinique	\N	0	t
54	organisation.batiments.index	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Voir la liste des bâtiments	organisation	view	Voir la liste des bâtiments	\N	0	t
55	organisation.batiments.store	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Créer un bâtiment	organisation	create	Créer un bâtiment	\N	0	t
56	organisation.batiments.update	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Modifier un bâtiment	organisation	edit	Modifier un bâtiment	\N	0	t
57	organisation.batiments.destroy	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Supprimer un bâtiment	organisation	delete	Supprimer un bâtiment	\N	0	t
58	organisation.batiments.toggle-status	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Activer/Désactiver un bâtiment	organisation	toggle	Activer/Désactiver un bâtiment	\N	0	t
59	organisation.etages.index	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Voir la liste des étages	organisation	view	Voir la liste des étages	\N	0	t
60	organisation.etages.store	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Créer un étage	organisation	create	Créer un étage	\N	0	t
61	organisation.etages.update	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Modifier un étage	organisation	edit	Modifier un étage	\N	0	t
62	organisation.etages.destroy	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Supprimer un étage	organisation	delete	Supprimer un étage	\N	0	t
63	organisation.etages.toggle-status	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Activer/Désactiver un étage	organisation	toggle	Activer/Désactiver un étage	\N	0	t
64	organisation.locaux.index	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Voir la liste des locaux	organisation	view	Voir la liste des locaux	\N	0	t
65	organisation.locaux.store	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Créer un local	organisation	create	Créer un local	\N	0	t
66	organisation.locaux.update	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Modifier un local	organisation	edit	Modifier un local	\N	0	t
67	organisation.locaux.destroy	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Supprimer un local	organisation	delete	Supprimer un local	\N	0	t
68	organisation.locaux.toggle-status	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Activer/Désactiver un local	organisation	toggle	Activer/Désactiver un local	\N	0	t
69	organisation.postes.index	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Voir la liste des postes de travail	organisation	view	Voir la liste des postes de travail	\N	0	t
70	organisation.postes.store	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Créer un poste de travail	organisation	create	Créer un poste de travail	\N	0	t
71	organisation.postes.update	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Modifier un poste de travail	organisation	edit	Modifier un poste de travail	\N	0	t
72	organisation.postes.destroy	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Supprimer un poste de travail	organisation	delete	Supprimer un poste de travail	\N	0	t
73	organisation.postes.toggle-status	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Activer/Désactiver un poste de travail	organisation	toggle	Activer/Désactiver un poste de travail	\N	0	t
74	parcinfo.dashboard.view	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Voir le tableau de bord Parc Info	parcinfo	view	Voir le tableau de bord Parc Info	\N	0	t
75	parcinfo.ordinateurs.index	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Voir la liste des ordinateurs	parcinfo	view	Voir la liste des ordinateurs	\N	0	t
76	parcinfo.ordinateurs.store	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Créer un ordinateur	parcinfo	create	Créer un ordinateur	\N	0	t
77	parcinfo.ordinateurs.update	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Modifier un ordinateur	parcinfo	edit	Modifier un ordinateur	\N	0	t
78	parcinfo.ordinateurs.destroy	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Supprimer un ordinateur	parcinfo	delete	Supprimer un ordinateur	\N	0	t
79	parcinfo.serveurs.index	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Voir la liste des serveurs	parcinfo	view	Voir la liste des serveurs	\N	0	t
80	parcinfo.serveurs.store	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Créer un serveur	parcinfo	create	Créer un serveur	\N	0	t
81	parcinfo.serveurs.update	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Modifier un serveur	parcinfo	edit	Modifier un serveur	\N	0	t
82	parcinfo.serveurs.destroy	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Supprimer un serveur	parcinfo	delete	Supprimer un serveur	\N	0	t
83	parcinfo.mobiles.index	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Voir la liste des terminaux mobiles	parcinfo	view	Voir la liste des terminaux mobiles	\N	0	t
84	parcinfo.mobiles.store	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Créer un terminal mobile	parcinfo	create	Créer un terminal mobile	\N	0	t
85	parcinfo.mobiles.update	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Modifier un terminal mobile	parcinfo	edit	Modifier un terminal mobile	\N	0	t
86	parcinfo.mobiles.destroy	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Supprimer un terminal mobile	parcinfo	delete	Supprimer un terminal mobile	\N	0	t
87	parcinfo.logiciels.index	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Voir le catalogue logiciels	parcinfo	view	Voir le catalogue logiciels	\N	0	t
88	parcinfo.logiciels.store	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Ajouter un logiciel	parcinfo	create	Ajouter un logiciel	\N	0	t
89	parcinfo.logiciels.update	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Modifier un logiciel	parcinfo	edit	Modifier un logiciel	\N	0	t
90	parcinfo.logiciels.destroy	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Supprimer un logiciel	parcinfo	delete	Supprimer un logiciel	\N	0	t
91	parcinfo.fournisseurs.index	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Voir la liste des fournisseurs	parcinfo	view	Voir la liste des fournisseurs	\N	0	t
92	parcinfo.fournisseurs.store	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Créer un fournisseur	parcinfo	create	Créer un fournisseur	\N	0	t
93	parcinfo.fournisseurs.update	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Modifier un fournisseur	parcinfo	edit	Modifier un fournisseur	\N	0	t
94	parcinfo.fournisseurs.destroy	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Supprimer un fournisseur	parcinfo	delete	Supprimer un fournisseur	\N	0	t
95	parcinfo.licences.index	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Voir la liste des licences	parcinfo	view	Voir la liste des licences	\N	0	t
96	parcinfo.licences.store	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Créer une licence	parcinfo	create	Créer une licence	\N	0	t
97	parcinfo.licences.update	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Modifier une licence	parcinfo	edit	Modifier une licence	\N	0	t
98	parcinfo.licences.destroy	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Supprimer une licence	parcinfo	delete	Supprimer une licence	\N	0	t
99	parcinfo.consommables.index	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Voir le stock consommables	parcinfo	view	Voir le stock consommables	\N	0	t
100	parcinfo.consommables.store	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Ajouter un consommable	parcinfo	create	Ajouter un consommable	\N	0	t
101	parcinfo.consommables.update	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Modifier un consommable	parcinfo	edit	Modifier un consommable	\N	0	t
102	parcinfo.consommables.destroy	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Supprimer un consommable	parcinfo	delete	Supprimer un consommable	\N	0	t
103	parcinfo.switches.index	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Voir la liste des switches	parcinfo	view	Voir la liste des switches	\N	0	t
104	parcinfo.routeurs.index	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Voir la liste des routeurs	parcinfo	view	Voir la liste des routeurs	\N	0	t
105	parcinfo.wifi.index	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Voir la liste des points d'accès WiFi	parcinfo	view	Voir la liste des points d'accès WiFi	\N	0	t
106	parcinfo.parefeux.index	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Voir la liste des pare-feux	parcinfo	view	Voir la liste des pare-feux	\N	0	t
107	parcinfo.onduleurs.index	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Voir la liste des onduleurs	parcinfo	view	Voir la liste des onduleurs	\N	0	t
108	parcinfo.racks.index	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Voir la liste des baies & racks	parcinfo	view	Voir la liste des baies & racks	\N	0	t
109	parcinfo.brassage.index	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Voir la liste de brassage	parcinfo	view	Voir la liste de brassage	\N	0	t
110	parcinfo.cameras.index	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Voir la liste des caméras IP	parcinfo	view	Voir la liste des caméras IP	\N	0	t
111	parcinfo.imprimantes.index	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Voir la liste des imprimantes	parcinfo	view	Voir la liste des imprimantes	\N	0	t
112	parcinfo.scanners.index	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Voir la liste des scanners	parcinfo	view	Voir la liste des scanners	\N	0	t
113	parcinfo.telephonie.index	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Voir la liste des téléphones fixes	parcinfo	view	Voir la liste des téléphones fixes	\N	0	t
114	parcinfo.terminaux-ip.index	web	2026-05-21 19:30:13	2026-05-21 19:30:13	Voir la liste des terminaux IP	parcinfo	view	Voir la liste des terminaux IP	\N	0	t
115	parc-info.referentiels.types-cpus.index	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Voir les types de CPU	parcinfo	view	Référentiel - Voir les types de CPU	\N	0	t
116	parc-info.referentiels.types-cpus.store	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Créer un type de CPU	parcinfo	create	Référentiel - Créer un type de CPU	\N	0	t
117	parc-info.referentiels.types-cpus.update	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Modifier un type de CPU	parcinfo	edit	Référentiel - Modifier un type de CPU	\N	0	t
118	parc-info.referentiels.types-cpus.destroy	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Supprimer un type de CPU	parcinfo	delete	Référentiel - Supprimer un type de CPU	\N	0	t
119	parc-info.referentiels.types-disques.index	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Voir les types de disque	parcinfo	view	Référentiel - Voir les types de disque	\N	0	t
120	parc-info.referentiels.types-disques.store	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Créer un type de disque	parcinfo	create	Référentiel - Créer un type de disque	\N	0	t
121	parc-info.referentiels.types-disques.update	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Modifier un type de disque	parcinfo	edit	Référentiel - Modifier un type de disque	\N	0	t
122	parc-info.referentiels.types-disques.destroy	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Supprimer un type de disque	parcinfo	delete	Référentiel - Supprimer un type de disque	\N	0	t
123	parc-info.referentiels.types-os.index	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Voir les types d'OS	parcinfo	view	Référentiel - Voir les types d'OS	\N	0	t
124	parc-info.referentiels.types-os.store	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Créer un type d'OS	parcinfo	create	Référentiel - Créer un type d'OS	\N	0	t
125	parc-info.referentiels.types-os.update	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Modifier un type d'OS	parcinfo	edit	Référentiel - Modifier un type d'OS	\N	0	t
126	parc-info.referentiels.types-os.destroy	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Supprimer un type d'OS	parcinfo	delete	Référentiel - Supprimer un type d'OS	\N	0	t
127	parc-info.referentiels.types-rams.index	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Voir les types de RAM	parcinfo	view	Référentiel - Voir les types de RAM	\N	0	t
128	parc-info.referentiels.types-rams.store	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Créer un type de RAM	parcinfo	create	Référentiel - Créer un type de RAM	\N	0	t
129	parc-info.referentiels.types-rams.update	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Modifier un type de RAM	parcinfo	edit	Référentiel - Modifier un type de RAM	\N	0	t
130	parc-info.referentiels.types-rams.destroy	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Supprimer un type de RAM	parcinfo	delete	Référentiel - Supprimer un type de RAM	\N	0	t
131	parc-info.referentiels.marques.index	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Voir les marques	parcinfo	view	Référentiel - Voir les marques	\N	0	t
132	parc-info.referentiels.marques.store	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Créer une marque	parcinfo	create	Référentiel - Créer une marque	\N	0	t
133	parc-info.referentiels.marques.update	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Modifier une marque	parcinfo	edit	Référentiel - Modifier une marque	\N	0	t
134	parc-info.referentiels.marques.destroy	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Supprimer une marque	parcinfo	delete	Référentiel - Supprimer une marque	\N	0	t
135	parc-info.referentiels.types-imprimantes.index	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Voir les types d'imprimante	parcinfo	view	Référentiel - Voir les types d'imprimante	\N	0	t
136	parc-info.referentiels.types-imprimantes.store	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Créer un type d'imprimante	parcinfo	create	Référentiel - Créer un type d'imprimante	\N	0	t
137	parc-info.referentiels.types-imprimantes.update	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Modifier un type d'imprimante	parcinfo	edit	Référentiel - Modifier un type d'imprimante	\N	0	t
138	parc-info.referentiels.types-imprimantes.destroy	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Supprimer un type d'imprimante	parcinfo	delete	Référentiel - Supprimer un type d'imprimante	\N	0	t
139	parc-info.referentiels.types-mobiles.index	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Voir les types de mobile	parcinfo	view	Référentiel - Voir les types de mobile	\N	0	t
140	parc-info.referentiels.types-mobiles.store	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Créer un type de mobile	parcinfo	create	Référentiel - Créer un type de mobile	\N	0	t
141	parc-info.referentiels.types-mobiles.update	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Modifier un type de mobile	parcinfo	edit	Référentiel - Modifier un type de mobile	\N	0	t
142	parc-info.referentiels.types-mobiles.destroy	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Supprimer un type de mobile	parcinfo	delete	Référentiel - Supprimer un type de mobile	\N	0	t
143	parc-info.referentiels.types-reseaux.index	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Voir les types d'équipement réseau	parcinfo	view	Référentiel - Voir les types d'équipement réseau	\N	0	t
144	parc-info.referentiels.types-reseaux.store	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Créer un type d'équipement réseau	parcinfo	create	Référentiel - Créer un type d'équipement réseau	\N	0	t
145	parc-info.referentiels.types-reseaux.update	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Modifier un type d'équipement réseau	parcinfo	edit	Référentiel - Modifier un type d'équipement réseau	\N	0	t
146	parc-info.referentiels.types-reseaux.destroy	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Supprimer un type d'équipement réseau	parcinfo	delete	Référentiel - Supprimer un type d'équipement réseau	\N	0	t
147	parc-info.referentiels.types-infrastructures.index	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Voir les types d'infrastructure	parcinfo	view	Référentiel - Voir les types d'infrastructure	\N	0	t
148	parc-info.referentiels.types-infrastructures.store	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Créer un type d'infrastructure	parcinfo	create	Référentiel - Créer un type d'infrastructure	\N	0	t
149	parc-info.referentiels.types-infrastructures.update	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Modifier un type d'infrastructure	parcinfo	edit	Référentiel - Modifier un type d'infrastructure	\N	0	t
150	parc-info.referentiels.types-infrastructures.destroy	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Supprimer un type d'infrastructure	parcinfo	delete	Référentiel - Supprimer un type d'infrastructure	\N	0	t
151	parc-info.referentiels.types-licences.index	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Voir les types de licence	parcinfo	view	Référentiel - Voir les types de licence	\N	0	t
152	parc-info.referentiels.types-licences.store	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Créer un type de licence	parcinfo	create	Référentiel - Créer un type de licence	\N	0	t
153	parc-info.referentiels.types-licences.update	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Modifier un type de licence	parcinfo	edit	Référentiel - Modifier un type de licence	\N	0	t
154	parc-info.referentiels.types-licences.destroy	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Supprimer un type de licence	parcinfo	delete	Référentiel - Supprimer un type de licence	\N	0	t
155	parc-info.referentiels.types-consommables.index	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Voir les types de consommable	parcinfo	view	Référentiel - Voir les types de consommable	\N	0	t
156	parc-info.referentiels.types-consommables.store	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Créer un type de consommable	parcinfo	create	Référentiel - Créer un type de consommable	\N	0	t
157	parc-info.referentiels.types-consommables.update	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Modifier un type de consommable	parcinfo	edit	Référentiel - Modifier un type de consommable	\N	0	t
158	parc-info.referentiels.types-consommables.destroy	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Supprimer un type de consommable	parcinfo	delete	Référentiel - Supprimer un type de consommable	\N	0	t
159	parc-info.referentiels.editeurs.index	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Voir les éditeurs	parcinfo	view	Référentiel - Voir les éditeurs	\N	0	t
160	parc-info.referentiels.editeurs.store	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Créer un éditeur	parcinfo	create	Référentiel - Créer un éditeur	\N	0	t
161	parc-info.referentiels.editeurs.update	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Modifier un éditeur	parcinfo	edit	Référentiel - Modifier un éditeur	\N	0	t
162	parc-info.referentiels.editeurs.destroy	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Référentiel - Supprimer un éditeur	parcinfo	edit	Référentiel - Supprimer un éditeur	\N	0	t
163	parc-info.analyse.etats.view	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Analyse - Voir les états des équipements	parcinfo	view	Analyse - Voir les états des équipements	\N	0	t
164	parc-info.analyse.statistiques.view	web	2026-06-12 19:38:29	2026-06-12 19:38:29	Analyse - Voir les statistiques	parcinfo	view	Analyse - Voir les statistiques	\N	0	t
\.


--
-- Data for Name: role_has_permissions; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.role_has_permissions (permission_id, role_id) FROM stdin;
1	1
2	1
3	1
4	1
5	1
6	1
7	1
8	1
9	1
10	1
11	1
12	1
13	1
14	1
15	1
16	1
17	1
18	1
19	1
20	1
21	1
22	1
23	1
24	1
25	1
26	1
27	1
28	1
29	1
30	1
31	1
32	1
33	1
34	1
35	1
36	1
37	1
38	1
39	1
40	1
41	1
42	1
43	1
44	1
45	1
46	1
47	1
48	1
49	1
50	1
51	1
52	1
53	1
54	1
55	1
56	1
57	1
58	1
59	1
60	1
61	1
62	1
63	1
64	1
65	1
66	1
67	1
68	1
69	1
70	1
71	1
72	1
73	1
74	1
75	1
76	1
77	1
78	1
79	1
80	1
81	1
82	1
83	1
84	1
85	1
86	1
87	1
88	1
89	1
90	1
91	1
92	1
93	1
94	1
95	1
96	1
97	1
98	1
99	1
100	1
101	1
102	1
103	1
104	1
105	1
106	1
107	1
108	1
109	1
110	1
111	1
112	1
113	1
114	1
1	2
2	2
3	2
4	2
\.


--
-- Data for Name: roles; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.roles (id, name, guard_name, created_at, updated_at, description) FROM stdin;
1	super-admin	web	2026-05-21 19:34:12	2026-05-21 19:34:12	\N
2	admin	web	2026-05-30 14:23:29	2026-05-30 14:23:29	\N
3	Gestionnaire	web	2026-06-16 09:36:55	2026-06-16 09:36:55	\N
\.


--
-- Data for Name: sessions; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.sessions (id, user_id, ip_address, user_agent, payload, last_activity) FROM stdin;
VFdK0effAUGVTncMAAMEW2vbMikdnWvMA7VQlNZq	3	127.0.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	YTo0OntzOjY6Il90b2tlbiI7czo0MDoiQWxLd2l1aWRGWkdyYlU3bjdaak9nRUJUb2cwRTRpQ0o3dU03b25QMSI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MztzOjk6Il9wcmV2aW91cyI7YToyOntzOjM6InVybCI7czo1NzoiaHR0cDovL3BhcmNfaW5mby5sb2NhbC9wYXJjLWluZm8vaW5mb3JtYXRpcXVlL29yZGluYXRldXJzIjtzOjU6InJvdXRlIjtzOjI3OiJwYXJjLWluZm8ub3JkaW5hdGV1cnMuaW5kZXgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19	1781892279
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: parc_info
--

COPY public.users (id, name, last_name, user_name, email, service, email_verified_at, password, remember_token, created_at, updated_at, is_active, avatar, dossier_employe_id) FROM stdin;
3	ibrahim	gninasse	igninasse	ibrahim.gninasse@gmail.com	\N	\N	$2y$12$tXPElQfILSGRVcxTYbAuPOhpO7ZUysMVVX8xjqzEq31NicFNnPAAW	E6C2XATGMZGl0uG9VcS9NuhBoC6shypcICj7Qk0BZ5Cp8GwqdFDD8M0HWphY	2026-05-21 19:32:54	2026-05-21 19:32:54	t	\N	\N
22	Elise	TOE	elise	elise@gmail.com	Direction des Services Informatiques	\N	$2y$12$8SDprbrU.J7QZgB5d5cjoOFCmlCSkn47.69.Mwyu0HK1tjqmJm2Y2	\N	2026-06-16 09:44:59	2026-06-16 09:44:59	t	\N	\N
\.


--
-- Name: activity_log_id_seq; Type: SEQUENCE SET; Schema: public; Owner: parc_info
--

SELECT pg_catalog.setval('public.activity_log_id_seq', 80, true);


--
-- Name: cores_users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: parc_info
--

SELECT pg_catalog.setval('public.cores_users_id_seq', 22, true);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: parc_info
--

SELECT pg_catalog.setval('public.failed_jobs_id_seq', 1, false);


--
-- Name: grh_contacts_employes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: parc_info
--

SELECT pg_catalog.setval('public.grh_contacts_employes_id_seq', 10, true);


--
-- Name: grh_dossiers_employes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: parc_info
--

SELECT pg_catalog.setval('public.grh_dossiers_employes_id_seq', 5, true);


--
-- Name: jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: parc_info
--

SELECT pg_catalog.setval('public.jobs_id_seq', 1, false);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: parc_info
--

SELECT pg_catalog.setval('public.migrations_id_seq', 55, true);


--
-- Name: modules_id_seq; Type: SEQUENCE SET; Schema: public; Owner: parc_info
--

SELECT pg_catalog.setval('public.modules_id_seq', 4, true);


--
-- Name: organisation_batiments_id_seq; Type: SEQUENCE SET; Schema: public; Owner: parc_info
--

SELECT pg_catalog.setval('public.organisation_batiments_id_seq', 5, true);


--
-- Name: organisation_directions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: parc_info
--

SELECT pg_catalog.setval('public.organisation_directions_id_seq', 13, true);


--
-- Name: organisation_etages_id_seq; Type: SEQUENCE SET; Schema: public; Owner: parc_info
--

SELECT pg_catalog.setval('public.organisation_etages_id_seq', 7, true);


--
-- Name: organisation_locaux_id_seq; Type: SEQUENCE SET; Schema: public; Owner: parc_info
--

SELECT pg_catalog.setval('public.organisation_locaux_id_seq', 10, true);


--
-- Name: organisation_postes_travail_id_seq; Type: SEQUENCE SET; Schema: public; Owner: parc_info
--

SELECT pg_catalog.setval('public.organisation_postes_travail_id_seq', 5, true);


--
-- Name: organisation_services_id_seq; Type: SEQUENCE SET; Schema: public; Owner: parc_info
--

SELECT pg_catalog.setval('public.organisation_services_id_seq', 39, true);


--
-- Name: organisation_sites_id_seq; Type: SEQUENCE SET; Schema: public; Owner: parc_info
--

SELECT pg_catalog.setval('public.organisation_sites_id_seq', 2, true);


--
-- Name: organisation_unites_id_seq; Type: SEQUENCE SET; Schema: public; Owner: parc_info
--

SELECT pg_catalog.setval('public.organisation_unites_id_seq', 34, true);


--
-- Name: parc_info_affectation_equipements_id_seq; Type: SEQUENCE SET; Schema: public; Owner: parc_info
--

SELECT pg_catalog.setval('public.parc_info_affectation_equipements_id_seq', 16, true);


--
-- Name: parc_info_affectations_consommables_id_seq; Type: SEQUENCE SET; Schema: public; Owner: parc_info
--

SELECT pg_catalog.setval('public.parc_info_affectations_consommables_id_seq', 2, true);


--
-- Name: parc_info_affectations_licences_id_seq; Type: SEQUENCE SET; Schema: public; Owner: parc_info
--

SELECT pg_catalog.setval('public.parc_info_affectations_licences_id_seq', 3, true);


--
-- Name: parc_info_consommables_id_seq; Type: SEQUENCE SET; Schema: public; Owner: parc_info
--

SELECT pg_catalog.setval('public.parc_info_consommables_id_seq', 1, true);


--
-- Name: parc_info_contacts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: parc_info
--

SELECT pg_catalog.setval('public.parc_info_contacts_id_seq', 1, true);


--
-- Name: parc_info_contrats_maintenances_id_seq; Type: SEQUENCE SET; Schema: public; Owner: parc_info
--

SELECT pg_catalog.setval('public.parc_info_contrats_maintenances_id_seq', 1, false);


--
-- Name: parc_info_documents_licences_id_seq; Type: SEQUENCE SET; Schema: public; Owner: parc_info
--

SELECT pg_catalog.setval('public.parc_info_documents_licences_id_seq', 1, false);


--
-- Name: parc_info_editeurs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: parc_info
--

SELECT pg_catalog.setval('public.parc_info_editeurs_id_seq', 2, true);


--
-- Name: parc_info_equipements_id_seq; Type: SEQUENCE SET; Schema: public; Owner: parc_info
--

SELECT pg_catalog.setval('public.parc_info_equipements_id_seq', 21, true);


--
-- Name: parc_info_fournisseurs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: parc_info
--

SELECT pg_catalog.setval('public.parc_info_fournisseurs_id_seq', 2, true);


--
-- Name: parc_info_historique_changements_id_seq; Type: SEQUENCE SET; Schema: public; Owner: parc_info
--

SELECT pg_catalog.setval('public.parc_info_historique_changements_id_seq', 37, true);


--
-- Name: parc_info_licences_id_seq; Type: SEQUENCE SET; Schema: public; Owner: parc_info
--

SELECT pg_catalog.setval('public.parc_info_licences_id_seq', 1, true);


--
-- Name: parc_info_logiciels_id_seq; Type: SEQUENCE SET; Schema: public; Owner: parc_info
--

SELECT pg_catalog.setval('public.parc_info_logiciels_id_seq', 2, true);


--
-- Name: parc_info_marques_id_seq; Type: SEQUENCE SET; Schema: public; Owner: parc_info
--

SELECT pg_catalog.setval('public.parc_info_marques_id_seq', 7, true);


--
-- Name: parc_info_mouvements_consommables_id_seq; Type: SEQUENCE SET; Schema: public; Owner: parc_info
--

SELECT pg_catalog.setval('public.parc_info_mouvements_consommables_id_seq', 7, true);


--
-- Name: parc_info_types_consommables_id_seq; Type: SEQUENCE SET; Schema: public; Owner: parc_info
--

SELECT pg_catalog.setval('public.parc_info_types_consommables_id_seq', 1, true);


--
-- Name: parc_info_types_cpus_id_seq; Type: SEQUENCE SET; Schema: public; Owner: parc_info
--

SELECT pg_catalog.setval('public.parc_info_types_cpus_id_seq', 9, true);


--
-- Name: parc_info_types_disques_id_seq; Type: SEQUENCE SET; Schema: public; Owner: parc_info
--

SELECT pg_catalog.setval('public.parc_info_types_disques_id_seq', 2, true);


--
-- Name: parc_info_types_imprimantes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: parc_info
--

SELECT pg_catalog.setval('public.parc_info_types_imprimantes_id_seq', 1, false);


--
-- Name: parc_info_types_infrastructures_id_seq; Type: SEQUENCE SET; Schema: public; Owner: parc_info
--

SELECT pg_catalog.setval('public.parc_info_types_infrastructures_id_seq', 1, false);


--
-- Name: parc_info_types_licences_id_seq; Type: SEQUENCE SET; Schema: public; Owner: parc_info
--

SELECT pg_catalog.setval('public.parc_info_types_licences_id_seq', 6, true);


--
-- Name: parc_info_types_mobiles_id_seq; Type: SEQUENCE SET; Schema: public; Owner: parc_info
--

SELECT pg_catalog.setval('public.parc_info_types_mobiles_id_seq', 2, true);


--
-- Name: parc_info_types_os_id_seq; Type: SEQUENCE SET; Schema: public; Owner: parc_info
--

SELECT pg_catalog.setval('public.parc_info_types_os_id_seq', 4, true);


--
-- Name: parc_info_types_rams_id_seq; Type: SEQUENCE SET; Schema: public; Owner: parc_info
--

SELECT pg_catalog.setval('public.parc_info_types_rams_id_seq', 3, true);


--
-- Name: parc_info_types_reseaux_id_seq; Type: SEQUENCE SET; Schema: public; Owner: parc_info
--

SELECT pg_catalog.setval('public.parc_info_types_reseaux_id_seq', 3, true);


--
-- Name: permissions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: parc_info
--

SELECT pg_catalog.setval('public.permissions_id_seq', 164, true);


--
-- Name: roles_id_seq; Type: SEQUENCE SET; Schema: public; Owner: parc_info
--

SELECT pg_catalog.setval('public.roles_id_seq', 3, true);


--
-- Name: activity_log activity_log_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.activity_log
    ADD CONSTRAINT activity_log_pkey PRIMARY KEY (id);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: users cores_users_email_unique; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT cores_users_email_unique UNIQUE (email);


--
-- Name: users cores_users_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT cores_users_pkey PRIMARY KEY (id);


--
-- Name: users cores_users_user_name_unique; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT cores_users_user_name_unique UNIQUE (user_name);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: grh_contacts_employes grh_contacts_employes_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.grh_contacts_employes
    ADD CONSTRAINT grh_contacts_employes_pkey PRIMARY KEY (id);


--
-- Name: grh_dossiers_employes grh_dossiers_employes_matricule_unique; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.grh_dossiers_employes
    ADD CONSTRAINT grh_dossiers_employes_matricule_unique UNIQUE (matricule);


--
-- Name: grh_dossiers_employes grh_dossiers_employes_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.grh_dossiers_employes
    ADD CONSTRAINT grh_dossiers_employes_pkey PRIMARY KEY (id);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: model_has_permissions model_has_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.model_has_permissions
    ADD CONSTRAINT model_has_permissions_pkey PRIMARY KEY (permission_id, model_id, model_type);


--
-- Name: model_has_roles model_has_roles_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.model_has_roles
    ADD CONSTRAINT model_has_roles_pkey PRIMARY KEY (role_id, model_id, model_type);


--
-- Name: modules modules_name_unique; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.modules
    ADD CONSTRAINT modules_name_unique UNIQUE (name);


--
-- Name: modules modules_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.modules
    ADD CONSTRAINT modules_pkey PRIMARY KEY (id);


--
-- Name: modules modules_slug_unique; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.modules
    ADD CONSTRAINT modules_slug_unique UNIQUE (slug);


--
-- Name: organisation_batiments organisation_batiments_code_unique; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.organisation_batiments
    ADD CONSTRAINT organisation_batiments_code_unique UNIQUE (code);


--
-- Name: organisation_batiments organisation_batiments_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.organisation_batiments
    ADD CONSTRAINT organisation_batiments_pkey PRIMARY KEY (id);


--
-- Name: organisation_directions organisation_directions_code_unique; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.organisation_directions
    ADD CONSTRAINT organisation_directions_code_unique UNIQUE (code);


--
-- Name: organisation_directions organisation_directions_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.organisation_directions
    ADD CONSTRAINT organisation_directions_pkey PRIMARY KEY (id);


--
-- Name: organisation_etages organisation_etages_batiment_id_numero_unique; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.organisation_etages
    ADD CONSTRAINT organisation_etages_batiment_id_numero_unique UNIQUE (batiment_id, numero);


--
-- Name: organisation_etages organisation_etages_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.organisation_etages
    ADD CONSTRAINT organisation_etages_pkey PRIMARY KEY (id);


--
-- Name: organisation_locaux organisation_locaux_code_unique; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.organisation_locaux
    ADD CONSTRAINT organisation_locaux_code_unique UNIQUE (code);


--
-- Name: organisation_locaux organisation_locaux_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.organisation_locaux
    ADD CONSTRAINT organisation_locaux_pkey PRIMARY KEY (id);


--
-- Name: organisation_postes_travail organisation_postes_travail_code_unique; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.organisation_postes_travail
    ADD CONSTRAINT organisation_postes_travail_code_unique UNIQUE (code);


--
-- Name: organisation_postes_travail organisation_postes_travail_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.organisation_postes_travail
    ADD CONSTRAINT organisation_postes_travail_pkey PRIMARY KEY (id);


--
-- Name: organisation_services organisation_services_code_unique; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.organisation_services
    ADD CONSTRAINT organisation_services_code_unique UNIQUE (code);


--
-- Name: organisation_services organisation_services_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.organisation_services
    ADD CONSTRAINT organisation_services_pkey PRIMARY KEY (id);


--
-- Name: organisation_sites organisation_sites_code_unique; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.organisation_sites
    ADD CONSTRAINT organisation_sites_code_unique UNIQUE (code);


--
-- Name: organisation_sites organisation_sites_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.organisation_sites
    ADD CONSTRAINT organisation_sites_pkey PRIMARY KEY (id);


--
-- Name: organisation_unites organisation_unites_code_unique; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.organisation_unites
    ADD CONSTRAINT organisation_unites_code_unique UNIQUE (code);


--
-- Name: organisation_unites organisation_unites_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.organisation_unites
    ADD CONSTRAINT organisation_unites_pkey PRIMARY KEY (id);


--
-- Name: parc_info_affectation_equipements parc_info_affectation_equipements_code_unique; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_affectation_equipements
    ADD CONSTRAINT parc_info_affectation_equipements_code_unique UNIQUE (code);


--
-- Name: parc_info_affectation_equipements parc_info_affectation_equipements_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_affectation_equipements
    ADD CONSTRAINT parc_info_affectation_equipements_pkey PRIMARY KEY (id);


--
-- Name: parc_info_affectations_consommables parc_info_affectations_consommables_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_affectations_consommables
    ADD CONSTRAINT parc_info_affectations_consommables_pkey PRIMARY KEY (id);


--
-- Name: parc_info_affectations_licences parc_info_affectations_licences_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_affectations_licences
    ADD CONSTRAINT parc_info_affectations_licences_pkey PRIMARY KEY (id);


--
-- Name: parc_info_cameras_ip parc_info_cameras_ip_adresse_mac_unique; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_cameras_ip
    ADD CONSTRAINT parc_info_cameras_ip_adresse_mac_unique UNIQUE (adresse_mac);


--
-- Name: parc_info_cameras_ip parc_info_cameras_ip_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_cameras_ip
    ADD CONSTRAINT parc_info_cameras_ip_pkey PRIMARY KEY (equipement_id);


--
-- Name: parc_info_consommables parc_info_consommables_code_unique; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_consommables
    ADD CONSTRAINT parc_info_consommables_code_unique UNIQUE (code);


--
-- Name: parc_info_consommables parc_info_consommables_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_consommables
    ADD CONSTRAINT parc_info_consommables_pkey PRIMARY KEY (id);


--
-- Name: parc_info_contacts parc_info_contacts_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_contacts
    ADD CONSTRAINT parc_info_contacts_pkey PRIMARY KEY (id);


--
-- Name: parc_info_contrats_maintenances parc_info_contrats_maintenances_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_contrats_maintenances
    ADD CONSTRAINT parc_info_contrats_maintenances_pkey PRIMARY KEY (id);


--
-- Name: parc_info_contrats_maintenances parc_info_contrats_maintenances_reference_unique; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_contrats_maintenances
    ADD CONSTRAINT parc_info_contrats_maintenances_reference_unique UNIQUE (reference);


--
-- Name: parc_info_documents_licences parc_info_documents_licences_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_documents_licences
    ADD CONSTRAINT parc_info_documents_licences_pkey PRIMARY KEY (id);


--
-- Name: parc_info_editeurs parc_info_editeurs_code_unique; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_editeurs
    ADD CONSTRAINT parc_info_editeurs_code_unique UNIQUE (code);


--
-- Name: parc_info_editeurs parc_info_editeurs_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_editeurs
    ADD CONSTRAINT parc_info_editeurs_pkey PRIMARY KEY (id);


--
-- Name: parc_info_equipements parc_info_equipements_code_inventaire_unique; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_equipements
    ADD CONSTRAINT parc_info_equipements_code_inventaire_unique UNIQUE (code_inventaire);


--
-- Name: parc_info_equipements parc_info_equipements_numero_serie_unique; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_equipements
    ADD CONSTRAINT parc_info_equipements_numero_serie_unique UNIQUE (numero_serie);


--
-- Name: parc_info_equipements parc_info_equipements_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_equipements
    ADD CONSTRAINT parc_info_equipements_pkey PRIMARY KEY (id);


--
-- Name: parc_info_equipements_reseaux parc_info_equipements_reseaux_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_equipements_reseaux
    ADD CONSTRAINT parc_info_equipements_reseaux_pkey PRIMARY KEY (equipement_id);


--
-- Name: parc_info_fournisseurs parc_info_fournisseurs_code_unique; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_fournisseurs
    ADD CONSTRAINT parc_info_fournisseurs_code_unique UNIQUE (code);


--
-- Name: parc_info_fournisseurs parc_info_fournisseurs_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_fournisseurs
    ADD CONSTRAINT parc_info_fournisseurs_pkey PRIMARY KEY (id);


--
-- Name: parc_info_historique_changements parc_info_historique_changements_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_historique_changements
    ADD CONSTRAINT parc_info_historique_changements_pkey PRIMARY KEY (id);


--
-- Name: parc_info_imprimantes parc_info_imprimantes_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_imprimantes
    ADD CONSTRAINT parc_info_imprimantes_pkey PRIMARY KEY (equipement_id);


--
-- Name: parc_info_infrastructures parc_info_infrastructures_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_infrastructures
    ADD CONSTRAINT parc_info_infrastructures_pkey PRIMARY KEY (equipement_id);


--
-- Name: parc_info_licences parc_info_licences_numero_contrat_unique; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_licences
    ADD CONSTRAINT parc_info_licences_numero_contrat_unique UNIQUE (numero_contrat);


--
-- Name: parc_info_licences parc_info_licences_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_licences
    ADD CONSTRAINT parc_info_licences_pkey PRIMARY KEY (id);


--
-- Name: parc_info_logiciels parc_info_logiciels_code_unique; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_logiciels
    ADD CONSTRAINT parc_info_logiciels_code_unique UNIQUE (code);


--
-- Name: parc_info_logiciels parc_info_logiciels_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_logiciels
    ADD CONSTRAINT parc_info_logiciels_pkey PRIMARY KEY (id);


--
-- Name: parc_info_marques parc_info_marques_libelle_unique; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_marques
    ADD CONSTRAINT parc_info_marques_libelle_unique UNIQUE (libelle);


--
-- Name: parc_info_marques parc_info_marques_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_marques
    ADD CONSTRAINT parc_info_marques_pkey PRIMARY KEY (id);


--
-- Name: parc_info_mobiles parc_info_mobiles_imei_1_unique; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_mobiles
    ADD CONSTRAINT parc_info_mobiles_imei_1_unique UNIQUE (imei_1);


--
-- Name: parc_info_mobiles parc_info_mobiles_imei_2_unique; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_mobiles
    ADD CONSTRAINT parc_info_mobiles_imei_2_unique UNIQUE (imei_2);


--
-- Name: parc_info_mobiles parc_info_mobiles_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_mobiles
    ADD CONSTRAINT parc_info_mobiles_pkey PRIMARY KEY (equipement_id);


--
-- Name: parc_info_mouvements_consommables parc_info_mouvements_consommables_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_mouvements_consommables
    ADD CONSTRAINT parc_info_mouvements_consommables_pkey PRIMARY KEY (id);


--
-- Name: parc_info_ordinateurs parc_info_ordinateurs_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_ordinateurs
    ADD CONSTRAINT parc_info_ordinateurs_pkey PRIMARY KEY (equipement_id);


--
-- Name: parc_info_scanners parc_info_scanners_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_scanners
    ADD CONSTRAINT parc_info_scanners_pkey PRIMARY KEY (equipement_id);


--
-- Name: parc_info_serveurs parc_info_serveurs_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_serveurs
    ADD CONSTRAINT parc_info_serveurs_pkey PRIMARY KEY (equipement_id);


--
-- Name: parc_info_serveurs_virtuels parc_info_serveurs_virtuels_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_serveurs_virtuels
    ADD CONSTRAINT parc_info_serveurs_virtuels_pkey PRIMARY KEY (equipement_id);


--
-- Name: parc_info_telephones parc_info_telephones_adresse_mac_ethernet_unique; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_telephones
    ADD CONSTRAINT parc_info_telephones_adresse_mac_ethernet_unique UNIQUE (adresse_mac_ethernet);


--
-- Name: parc_info_telephones parc_info_telephones_extension_unique; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_telephones
    ADD CONSTRAINT parc_info_telephones_extension_unique UNIQUE (extension);


--
-- Name: parc_info_telephones parc_info_telephones_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_telephones
    ADD CONSTRAINT parc_info_telephones_pkey PRIMARY KEY (equipement_id);


--
-- Name: parc_info_types_consommables parc_info_types_consommables_code_unique; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_types_consommables
    ADD CONSTRAINT parc_info_types_consommables_code_unique UNIQUE (code);


--
-- Name: parc_info_types_consommables parc_info_types_consommables_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_types_consommables
    ADD CONSTRAINT parc_info_types_consommables_pkey PRIMARY KEY (id);


--
-- Name: parc_info_types_cpus parc_info_types_cpus_libelle_unique; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_types_cpus
    ADD CONSTRAINT parc_info_types_cpus_libelle_unique UNIQUE (libelle);


--
-- Name: parc_info_types_cpus parc_info_types_cpus_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_types_cpus
    ADD CONSTRAINT parc_info_types_cpus_pkey PRIMARY KEY (id);


--
-- Name: parc_info_types_disques parc_info_types_disques_libelle_unique; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_types_disques
    ADD CONSTRAINT parc_info_types_disques_libelle_unique UNIQUE (libelle);


--
-- Name: parc_info_types_disques parc_info_types_disques_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_types_disques
    ADD CONSTRAINT parc_info_types_disques_pkey PRIMARY KEY (id);


--
-- Name: parc_info_types_imprimantes parc_info_types_imprimantes_libelle_unique; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_types_imprimantes
    ADD CONSTRAINT parc_info_types_imprimantes_libelle_unique UNIQUE (libelle);


--
-- Name: parc_info_types_imprimantes parc_info_types_imprimantes_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_types_imprimantes
    ADD CONSTRAINT parc_info_types_imprimantes_pkey PRIMARY KEY (id);


--
-- Name: parc_info_types_infrastructures parc_info_types_infrastructures_libelle_unique; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_types_infrastructures
    ADD CONSTRAINT parc_info_types_infrastructures_libelle_unique UNIQUE (libelle);


--
-- Name: parc_info_types_infrastructures parc_info_types_infrastructures_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_types_infrastructures
    ADD CONSTRAINT parc_info_types_infrastructures_pkey PRIMARY KEY (id);


--
-- Name: parc_info_types_licences parc_info_types_licences_code_unique; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_types_licences
    ADD CONSTRAINT parc_info_types_licences_code_unique UNIQUE (code);


--
-- Name: parc_info_types_licences parc_info_types_licences_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_types_licences
    ADD CONSTRAINT parc_info_types_licences_pkey PRIMARY KEY (id);


--
-- Name: parc_info_types_mobiles parc_info_types_mobiles_libelle_unique; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_types_mobiles
    ADD CONSTRAINT parc_info_types_mobiles_libelle_unique UNIQUE (libelle);


--
-- Name: parc_info_types_mobiles parc_info_types_mobiles_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_types_mobiles
    ADD CONSTRAINT parc_info_types_mobiles_pkey PRIMARY KEY (id);


--
-- Name: parc_info_types_os parc_info_types_os_libelle_unique; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_types_os
    ADD CONSTRAINT parc_info_types_os_libelle_unique UNIQUE (libelle);


--
-- Name: parc_info_types_os parc_info_types_os_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_types_os
    ADD CONSTRAINT parc_info_types_os_pkey PRIMARY KEY (id);


--
-- Name: parc_info_types_rams parc_info_types_rams_libelle_unique; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_types_rams
    ADD CONSTRAINT parc_info_types_rams_libelle_unique UNIQUE (libelle);


--
-- Name: parc_info_types_rams parc_info_types_rams_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_types_rams
    ADD CONSTRAINT parc_info_types_rams_pkey PRIMARY KEY (id);


--
-- Name: parc_info_types_reseaux parc_info_types_reseaux_libelle_unique; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_types_reseaux
    ADD CONSTRAINT parc_info_types_reseaux_libelle_unique UNIQUE (libelle);


--
-- Name: parc_info_types_reseaux parc_info_types_reseaux_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_types_reseaux
    ADD CONSTRAINT parc_info_types_reseaux_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: permissions permissions_name_guard_name_unique; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_name_guard_name_unique UNIQUE (name, guard_name);


--
-- Name: permissions permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_pkey PRIMARY KEY (id);


--
-- Name: role_has_permissions role_has_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_pkey PRIMARY KEY (permission_id, role_id);


--
-- Name: roles roles_name_guard_name_unique; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_name_guard_name_unique UNIQUE (name, guard_name);


--
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: activity_log_causer_id_index; Type: INDEX; Schema: public; Owner: parc_info
--

CREATE INDEX activity_log_causer_id_index ON public.activity_log USING btree (causer_id);


--
-- Name: activity_log_created_at_index; Type: INDEX; Schema: public; Owner: parc_info
--

CREATE INDEX activity_log_created_at_index ON public.activity_log USING btree (created_at);


--
-- Name: activity_log_expires_at_index; Type: INDEX; Schema: public; Owner: parc_info
--

CREATE INDEX activity_log_expires_at_index ON public.activity_log USING btree (expires_at);


--
-- Name: activity_log_log_name_index; Type: INDEX; Schema: public; Owner: parc_info
--

CREATE INDEX activity_log_log_name_index ON public.activity_log USING btree (log_name);


--
-- Name: activity_log_module_index; Type: INDEX; Schema: public; Owner: parc_info
--

CREATE INDEX activity_log_module_index ON public.activity_log USING btree (module);


--
-- Name: activity_log_subject_id_index; Type: INDEX; Schema: public; Owner: parc_info
--

CREATE INDEX activity_log_subject_id_index ON public.activity_log USING btree (subject_id);


--
-- Name: causer; Type: INDEX; Schema: public; Owner: parc_info
--

CREATE INDEX causer ON public.activity_log USING btree (causer_type, causer_id);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: parc_info
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: model_has_permissions_model_id_model_type_index; Type: INDEX; Schema: public; Owner: parc_info
--

CREATE INDEX model_has_permissions_model_id_model_type_index ON public.model_has_permissions USING btree (model_id, model_type);


--
-- Name: model_has_roles_model_id_model_type_index; Type: INDEX; Schema: public; Owner: parc_info
--

CREATE INDEX model_has_roles_model_id_model_type_index ON public.model_has_roles USING btree (model_id, model_type);


--
-- Name: modules_is_active_index; Type: INDEX; Schema: public; Owner: parc_info
--

CREATE INDEX modules_is_active_index ON public.modules USING btree (is_active);


--
-- Name: modules_is_required_index; Type: INDEX; Schema: public; Owner: parc_info
--

CREATE INDEX modules_is_required_index ON public.modules USING btree (is_required);


--
-- Name: modules_slug_index; Type: INDEX; Schema: public; Owner: parc_info
--

CREATE INDEX modules_slug_index ON public.modules USING btree (slug);


--
-- Name: organisation_postes_travail_agent_id_index; Type: INDEX; Schema: public; Owner: parc_info
--

CREATE INDEX organisation_postes_travail_agent_id_index ON public.organisation_postes_travail USING btree (dossier_employe_id);


--
-- Name: organisation_postes_travail_code_index; Type: INDEX; Schema: public; Owner: parc_info
--

CREATE INDEX organisation_postes_travail_code_index ON public.organisation_postes_travail USING btree (code);


--
-- Name: organisation_postes_travail_service_id_index; Type: INDEX; Schema: public; Owner: parc_info
--

CREATE INDEX organisation_postes_travail_service_id_index ON public.organisation_postes_travail USING btree (service_id);


--
-- Name: organisation_postes_travail_statut_index; Type: INDEX; Schema: public; Owner: parc_info
--

CREATE INDEX organisation_postes_travail_statut_index ON public.organisation_postes_travail USING btree (statut);


--
-- Name: parc_info_affectations_consommables_date_remplacement_prochain_; Type: INDEX; Schema: public; Owner: parc_info
--

CREATE INDEX parc_info_affectations_consommables_date_remplacement_prochain_ ON public.parc_info_affectations_consommables USING btree (date_remplacement_prochain_prevu);


--
-- Name: parc_info_affectations_consommables_equipement_id_index; Type: INDEX; Schema: public; Owner: parc_info
--

CREATE INDEX parc_info_affectations_consommables_equipement_id_index ON public.parc_info_affectations_consommables USING btree (equipement_id);


--
-- Name: parc_info_affectations_licences_employe_id_date_fin_affectation; Type: INDEX; Schema: public; Owner: parc_info
--

CREATE INDEX parc_info_affectations_licences_employe_id_date_fin_affectation ON public.parc_info_affectations_licences USING btree (employe_id, date_fin_affectation);


--
-- Name: parc_info_affectations_licences_equipement_id_index; Type: INDEX; Schema: public; Owner: parc_info
--

CREATE INDEX parc_info_affectations_licences_equipement_id_index ON public.parc_info_affectations_licences USING btree (equipement_id);


--
-- Name: parc_info_affectations_licences_licence_id_actif_index; Type: INDEX; Schema: public; Owner: parc_info
--

CREATE INDEX parc_info_affectations_licences_licence_id_actif_index ON public.parc_info_affectations_licences USING btree (licence_id, actif);


--
-- Name: parc_info_consommables_code_index; Type: INDEX; Schema: public; Owner: parc_info
--

CREATE INDEX parc_info_consommables_code_index ON public.parc_info_consommables USING btree (code);


--
-- Name: parc_info_consommables_quantite_stock_actuel_index; Type: INDEX; Schema: public; Owner: parc_info
--

CREATE INDEX parc_info_consommables_quantite_stock_actuel_index ON public.parc_info_consommables USING btree (quantite_stock_actuel);


--
-- Name: parc_info_consommables_type_consommable_id_est_actif_index; Type: INDEX; Schema: public; Owner: parc_info
--

CREATE INDEX parc_info_consommables_type_consommable_id_est_actif_index ON public.parc_info_consommables USING btree (type_consommable_id, est_actif);


--
-- Name: parc_info_documents_licences_licence_id_type_index; Type: INDEX; Schema: public; Owner: parc_info
--

CREATE INDEX parc_info_documents_licences_licence_id_type_index ON public.parc_info_documents_licences USING btree (licence_id, type);


--
-- Name: parc_info_editeurs_est_actif_index; Type: INDEX; Schema: public; Owner: parc_info
--

CREATE INDEX parc_info_editeurs_est_actif_index ON public.parc_info_editeurs USING btree (est_actif);


--
-- Name: parc_info_licences_fournisseur_id_index; Type: INDEX; Schema: public; Owner: parc_info
--

CREATE INDEX parc_info_licences_fournisseur_id_index ON public.parc_info_licences USING btree (fournisseur_id);


--
-- Name: parc_info_licences_logiciel_id_date_expiration_index; Type: INDEX; Schema: public; Owner: parc_info
--

CREATE INDEX parc_info_licences_logiciel_id_date_expiration_index ON public.parc_info_licences USING btree (logiciel_id, date_expiration);


--
-- Name: parc_info_licences_statut_actif_index; Type: INDEX; Schema: public; Owner: parc_info
--

CREATE INDEX parc_info_licences_statut_actif_index ON public.parc_info_licences USING btree (statut, actif);


--
-- Name: parc_info_logiciels_code_index; Type: INDEX; Schema: public; Owner: parc_info
--

CREATE INDEX parc_info_logiciels_code_index ON public.parc_info_logiciels USING btree (code);


--
-- Name: parc_info_logiciels_editeur_id_est_actif_index; Type: INDEX; Schema: public; Owner: parc_info
--

CREATE INDEX parc_info_logiciels_editeur_id_est_actif_index ON public.parc_info_logiciels USING btree (editeur_id, est_actif);


--
-- Name: parc_info_mouvements_consommables_consommable_id_date_mouvement; Type: INDEX; Schema: public; Owner: parc_info
--

CREATE INDEX parc_info_mouvements_consommables_consommable_id_date_mouvement ON public.parc_info_mouvements_consommables USING btree (consommable_id, date_mouvement);


--
-- Name: parc_info_mouvements_consommables_type_mouvement_index; Type: INDEX; Schema: public; Owner: parc_info
--

CREATE INDEX parc_info_mouvements_consommables_type_mouvement_index ON public.parc_info_mouvements_consommables USING btree (type_mouvement);


--
-- Name: parc_info_mouvements_consommables_utilisateur_id_index; Type: INDEX; Schema: public; Owner: parc_info
--

CREATE INDEX parc_info_mouvements_consommables_utilisateur_id_index ON public.parc_info_mouvements_consommables USING btree (utilisateur_id);


--
-- Name: parc_info_types_consommables_categorie_index; Type: INDEX; Schema: public; Owner: parc_info
--

CREATE INDEX parc_info_types_consommables_categorie_index ON public.parc_info_types_consommables USING btree (categorie);


--
-- Name: permissions_category_index; Type: INDEX; Schema: public; Owner: parc_info
--

CREATE INDEX permissions_category_index ON public.permissions USING btree (category);


--
-- Name: permissions_module_index; Type: INDEX; Schema: public; Owner: parc_info
--

CREATE INDEX permissions_module_index ON public.permissions USING btree (module);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: parc_info
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: parc_info
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: subject; Type: INDEX; Schema: public; Owner: parc_info
--

CREATE INDEX subject ON public.activity_log USING btree (subject_type, subject_id);


--
-- Name: grh_contacts_employes grh_contacts_employes_employe_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.grh_contacts_employes
    ADD CONSTRAINT grh_contacts_employes_employe_id_foreign FOREIGN KEY (employe_id) REFERENCES public.grh_dossiers_employes(id) ON DELETE CASCADE;


--
-- Name: grh_dossiers_employes grh_dossiers_employes_direction_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.grh_dossiers_employes
    ADD CONSTRAINT grh_dossiers_employes_direction_id_foreign FOREIGN KEY (direction_id) REFERENCES public.organisation_directions(id);


--
-- Name: grh_dossiers_employes grh_dossiers_employes_service_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.grh_dossiers_employes
    ADD CONSTRAINT grh_dossiers_employes_service_id_foreign FOREIGN KEY (service_id) REFERENCES public.organisation_services(id);


--
-- Name: grh_dossiers_employes grh_dossiers_employes_unite_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.grh_dossiers_employes
    ADD CONSTRAINT grh_dossiers_employes_unite_id_foreign FOREIGN KEY (unite_id) REFERENCES public.organisation_unites(id);


--
-- Name: model_has_permissions model_has_permissions_permission_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.model_has_permissions
    ADD CONSTRAINT model_has_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES public.permissions(id) ON DELETE CASCADE;


--
-- Name: model_has_roles model_has_roles_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.model_has_roles
    ADD CONSTRAINT model_has_roles_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: organisation_batiments organisation_batiments_site_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.organisation_batiments
    ADD CONSTRAINT organisation_batiments_site_id_foreign FOREIGN KEY (site_id) REFERENCES public.organisation_sites(id) ON DELETE CASCADE;


--
-- Name: organisation_directions organisation_directions_responsable_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.organisation_directions
    ADD CONSTRAINT organisation_directions_responsable_id_foreign FOREIGN KEY (responsable_id) REFERENCES public.grh_dossiers_employes(id) ON DELETE SET NULL;


--
-- Name: organisation_directions organisation_directions_site_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.organisation_directions
    ADD CONSTRAINT organisation_directions_site_id_foreign FOREIGN KEY (site_id) REFERENCES public.organisation_sites(id);


--
-- Name: organisation_etages organisation_etages_batiment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.organisation_etages
    ADD CONSTRAINT organisation_etages_batiment_id_foreign FOREIGN KEY (batiment_id) REFERENCES public.organisation_batiments(id) ON DELETE CASCADE;


--
-- Name: organisation_locaux organisation_locaux_etage_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.organisation_locaux
    ADD CONSTRAINT organisation_locaux_etage_id_foreign FOREIGN KEY (etage_id) REFERENCES public.organisation_etages(id) ON DELETE CASCADE;


--
-- Name: organisation_postes_travail organisation_postes_travail_direction_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.organisation_postes_travail
    ADD CONSTRAINT organisation_postes_travail_direction_id_foreign FOREIGN KEY (direction_id) REFERENCES public.organisation_directions(id) ON DELETE RESTRICT;


--
-- Name: organisation_postes_travail organisation_postes_travail_dossier_employe_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.organisation_postes_travail
    ADD CONSTRAINT organisation_postes_travail_dossier_employe_id_foreign FOREIGN KEY (dossier_employe_id) REFERENCES public.grh_dossiers_employes(id) ON DELETE SET NULL;


--
-- Name: organisation_postes_travail organisation_postes_travail_local_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.organisation_postes_travail
    ADD CONSTRAINT organisation_postes_travail_local_id_foreign FOREIGN KEY (local_id) REFERENCES public.organisation_locaux(id) ON DELETE SET NULL;


--
-- Name: organisation_postes_travail organisation_postes_travail_service_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.organisation_postes_travail
    ADD CONSTRAINT organisation_postes_travail_service_id_foreign FOREIGN KEY (service_id) REFERENCES public.organisation_services(id) ON DELETE RESTRICT;


--
-- Name: organisation_postes_travail organisation_postes_travail_unite_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.organisation_postes_travail
    ADD CONSTRAINT organisation_postes_travail_unite_id_foreign FOREIGN KEY (unite_id) REFERENCES public.organisation_unites(id) ON DELETE SET NULL;


--
-- Name: organisation_services organisation_services_chef_service_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.organisation_services
    ADD CONSTRAINT organisation_services_chef_service_id_foreign FOREIGN KEY (chef_service_id) REFERENCES public.grh_dossiers_employes(id) ON DELETE SET NULL;


--
-- Name: organisation_services organisation_services_direction_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.organisation_services
    ADD CONSTRAINT organisation_services_direction_id_foreign FOREIGN KEY (direction_id) REFERENCES public.organisation_directions(id);


--
-- Name: organisation_services organisation_services_site_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.organisation_services
    ADD CONSTRAINT organisation_services_site_id_foreign FOREIGN KEY (site_id) REFERENCES public.organisation_sites(id);


--
-- Name: organisation_unites organisation_unites_major_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.organisation_unites
    ADD CONSTRAINT organisation_unites_major_id_foreign FOREIGN KEY (major_id) REFERENCES public.grh_dossiers_employes(id) ON DELETE SET NULL;


--
-- Name: organisation_unites organisation_unites_service_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.organisation_unites
    ADD CONSTRAINT organisation_unites_service_id_foreign FOREIGN KEY (service_id) REFERENCES public.organisation_services(id);


--
-- Name: organisation_unites organisation_unites_site_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.organisation_unites
    ADD CONSTRAINT organisation_unites_site_id_foreign FOREIGN KEY (site_id) REFERENCES public.organisation_sites(id);


--
-- Name: parc_info_affectation_equipements parc_info_affectation_equipements_direction_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_affectation_equipements
    ADD CONSTRAINT parc_info_affectation_equipements_direction_id_foreign FOREIGN KEY (direction_id) REFERENCES public.organisation_directions(id) ON DELETE SET NULL;


--
-- Name: parc_info_affectation_equipements parc_info_affectation_equipements_dossier_employe_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_affectation_equipements
    ADD CONSTRAINT parc_info_affectation_equipements_dossier_employe_id_foreign FOREIGN KEY (dossier_employe_id) REFERENCES public.grh_dossiers_employes(id) ON DELETE SET NULL;


--
-- Name: parc_info_affectation_equipements parc_info_affectation_equipements_equipement_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_affectation_equipements
    ADD CONSTRAINT parc_info_affectation_equipements_equipement_id_foreign FOREIGN KEY (equipement_id) REFERENCES public.parc_info_equipements(id) ON DELETE CASCADE;


--
-- Name: parc_info_affectation_equipements parc_info_affectation_equipements_local_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_affectation_equipements
    ADD CONSTRAINT parc_info_affectation_equipements_local_id_foreign FOREIGN KEY (local_id) REFERENCES public.organisation_locaux(id) ON DELETE SET NULL;


--
-- Name: parc_info_affectation_equipements parc_info_affectation_equipements_poste_travail_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_affectation_equipements
    ADD CONSTRAINT parc_info_affectation_equipements_poste_travail_id_foreign FOREIGN KEY (poste_travail_id) REFERENCES public.organisation_postes_travail(id) ON DELETE SET NULL;


--
-- Name: parc_info_affectation_equipements parc_info_affectation_equipements_service_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_affectation_equipements
    ADD CONSTRAINT parc_info_affectation_equipements_service_id_foreign FOREIGN KEY (service_id) REFERENCES public.organisation_services(id) ON DELETE SET NULL;


--
-- Name: parc_info_affectation_equipements parc_info_affectation_equipements_unite_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_affectation_equipements
    ADD CONSTRAINT parc_info_affectation_equipements_unite_id_foreign FOREIGN KEY (unite_id) REFERENCES public.organisation_unites(id) ON DELETE SET NULL;


--
-- Name: parc_info_affectations_consommables parc_info_affectations_consommables_consommable_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_affectations_consommables
    ADD CONSTRAINT parc_info_affectations_consommables_consommable_id_foreign FOREIGN KEY (consommable_id) REFERENCES public.parc_info_consommables(id) ON DELETE CASCADE;


--
-- Name: parc_info_affectations_consommables parc_info_affectations_consommables_equipement_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_affectations_consommables
    ADD CONSTRAINT parc_info_affectations_consommables_equipement_id_foreign FOREIGN KEY (equipement_id) REFERENCES public.parc_info_equipements(id) ON DELETE CASCADE;


--
-- Name: parc_info_affectations_licences parc_info_affectations_licences_employe_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_affectations_licences
    ADD CONSTRAINT parc_info_affectations_licences_employe_id_foreign FOREIGN KEY (employe_id) REFERENCES public.grh_dossiers_employes(id) ON DELETE SET NULL;


--
-- Name: parc_info_affectations_licences parc_info_affectations_licences_equipement_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_affectations_licences
    ADD CONSTRAINT parc_info_affectations_licences_equipement_id_foreign FOREIGN KEY (equipement_id) REFERENCES public.parc_info_equipements(id) ON DELETE SET NULL;


--
-- Name: parc_info_affectations_licences parc_info_affectations_licences_licence_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_affectations_licences
    ADD CONSTRAINT parc_info_affectations_licences_licence_id_foreign FOREIGN KEY (licence_id) REFERENCES public.parc_info_licences(id) ON DELETE CASCADE;


--
-- Name: parc_info_cameras_ip parc_info_cameras_ip_equipement_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_cameras_ip
    ADD CONSTRAINT parc_info_cameras_ip_equipement_id_foreign FOREIGN KEY (equipement_id) REFERENCES public.parc_info_equipements(id) ON DELETE CASCADE;


--
-- Name: parc_info_consommables parc_info_consommables_fournisseur_principal_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_consommables
    ADD CONSTRAINT parc_info_consommables_fournisseur_principal_id_foreign FOREIGN KEY (fournisseur_principal_id) REFERENCES public.parc_info_fournisseurs(id) ON DELETE RESTRICT;


--
-- Name: parc_info_consommables parc_info_consommables_marque_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_consommables
    ADD CONSTRAINT parc_info_consommables_marque_id_foreign FOREIGN KEY (marque_id) REFERENCES public.parc_info_marques(id) ON DELETE SET NULL;


--
-- Name: parc_info_consommables parc_info_consommables_type_consommable_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_consommables
    ADD CONSTRAINT parc_info_consommables_type_consommable_id_foreign FOREIGN KEY (type_consommable_id) REFERENCES public.parc_info_types_consommables(id) ON DELETE RESTRICT;


--
-- Name: parc_info_contacts parc_info_contacts_fournisseur_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_contacts
    ADD CONSTRAINT parc_info_contacts_fournisseur_id_foreign FOREIGN KEY (fournisseur_id) REFERENCES public.parc_info_fournisseurs(id) ON DELETE CASCADE;


--
-- Name: parc_info_contrats_maintenances parc_info_contrats_maintenances_fournisseur_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_contrats_maintenances
    ADD CONSTRAINT parc_info_contrats_maintenances_fournisseur_id_foreign FOREIGN KEY (fournisseur_id) REFERENCES public.parc_info_fournisseurs(id) ON DELETE SET NULL;


--
-- Name: parc_info_documents_licences parc_info_documents_licences_licence_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_documents_licences
    ADD CONSTRAINT parc_info_documents_licences_licence_id_foreign FOREIGN KEY (licence_id) REFERENCES public.parc_info_licences(id) ON DELETE CASCADE;


--
-- Name: parc_info_equipements parc_info_equipements_direction_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_equipements
    ADD CONSTRAINT parc_info_equipements_direction_id_foreign FOREIGN KEY (direction_id) REFERENCES public.organisation_directions(id) ON DELETE SET NULL;


--
-- Name: parc_info_equipements parc_info_equipements_marque_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_equipements
    ADD CONSTRAINT parc_info_equipements_marque_id_foreign FOREIGN KEY (marque_id) REFERENCES public.parc_info_marques(id) ON DELETE SET NULL;


--
-- Name: parc_info_equipements_reseaux parc_info_equipements_reseaux_equipement_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_equipements_reseaux
    ADD CONSTRAINT parc_info_equipements_reseaux_equipement_id_foreign FOREIGN KEY (equipement_id) REFERENCES public.parc_info_equipements(id) ON DELETE CASCADE;


--
-- Name: parc_info_equipements_reseaux parc_info_equipements_reseaux_type_reseau_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_equipements_reseaux
    ADD CONSTRAINT parc_info_equipements_reseaux_type_reseau_id_foreign FOREIGN KEY (type_reseau_id) REFERENCES public.parc_info_types_reseaux(id) ON DELETE SET NULL;


--
-- Name: parc_info_equipements parc_info_equipements_service_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_equipements
    ADD CONSTRAINT parc_info_equipements_service_id_foreign FOREIGN KEY (service_id) REFERENCES public.organisation_services(id) ON DELETE SET NULL;


--
-- Name: parc_info_equipements parc_info_equipements_unite_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_equipements
    ADD CONSTRAINT parc_info_equipements_unite_id_foreign FOREIGN KEY (unite_id) REFERENCES public.organisation_unites(id) ON DELETE SET NULL;


--
-- Name: parc_info_fournisseurs parc_info_fournisseurs_contact_principal_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_fournisseurs
    ADD CONSTRAINT parc_info_fournisseurs_contact_principal_id_foreign FOREIGN KEY (contact_principal_id) REFERENCES public.parc_info_contacts(id) ON DELETE SET NULL;


--
-- Name: parc_info_historique_changements parc_info_historique_changements_equipement_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_historique_changements
    ADD CONSTRAINT parc_info_historique_changements_equipement_id_foreign FOREIGN KEY (equipement_id) REFERENCES public.parc_info_equipements(id) ON DELETE CASCADE;


--
-- Name: parc_info_imprimantes parc_info_imprimantes_equipement_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_imprimantes
    ADD CONSTRAINT parc_info_imprimantes_equipement_id_foreign FOREIGN KEY (equipement_id) REFERENCES public.parc_info_equipements(id) ON DELETE CASCADE;


--
-- Name: parc_info_imprimantes parc_info_imprimantes_type_imprimante_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_imprimantes
    ADD CONSTRAINT parc_info_imprimantes_type_imprimante_id_foreign FOREIGN KEY (type_imprimante_id) REFERENCES public.parc_info_types_imprimantes(id) ON DELETE SET NULL;


--
-- Name: parc_info_infrastructures parc_info_infrastructures_equipement_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_infrastructures
    ADD CONSTRAINT parc_info_infrastructures_equipement_id_foreign FOREIGN KEY (equipement_id) REFERENCES public.parc_info_equipements(id) ON DELETE CASCADE;


--
-- Name: parc_info_infrastructures parc_info_infrastructures_type_infra_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_infrastructures
    ADD CONSTRAINT parc_info_infrastructures_type_infra_id_foreign FOREIGN KEY (type_infra_id) REFERENCES public.parc_info_types_infrastructures(id) ON DELETE SET NULL;


--
-- Name: parc_info_licences parc_info_licences_contact_support_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_licences
    ADD CONSTRAINT parc_info_licences_contact_support_id_foreign FOREIGN KEY (contact_support_id) REFERENCES public.parc_info_contacts(id) ON DELETE SET NULL;


--
-- Name: parc_info_licences parc_info_licences_contrat_maintenance_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_licences
    ADD CONSTRAINT parc_info_licences_contrat_maintenance_id_foreign FOREIGN KEY (contrat_maintenance_id) REFERENCES public.parc_info_contrats_maintenances(id) ON DELETE SET NULL;


--
-- Name: parc_info_licences parc_info_licences_fournisseur_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_licences
    ADD CONSTRAINT parc_info_licences_fournisseur_id_foreign FOREIGN KEY (fournisseur_id) REFERENCES public.parc_info_fournisseurs(id) ON DELETE RESTRICT;


--
-- Name: parc_info_licences parc_info_licences_logiciel_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_licences
    ADD CONSTRAINT parc_info_licences_logiciel_id_foreign FOREIGN KEY (logiciel_id) REFERENCES public.parc_info_logiciels(id) ON DELETE CASCADE;


--
-- Name: parc_info_logiciels parc_info_logiciels_editeur_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_logiciels
    ADD CONSTRAINT parc_info_logiciels_editeur_id_foreign FOREIGN KEY (editeur_id) REFERENCES public.parc_info_editeurs(id) ON DELETE RESTRICT;


--
-- Name: parc_info_logiciels parc_info_logiciels_type_licence_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_logiciels
    ADD CONSTRAINT parc_info_logiciels_type_licence_id_foreign FOREIGN KEY (type_licence_id) REFERENCES public.parc_info_types_licences(id) ON DELETE RESTRICT;


--
-- Name: parc_info_mobiles parc_info_mobiles_equipement_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_mobiles
    ADD CONSTRAINT parc_info_mobiles_equipement_id_foreign FOREIGN KEY (equipement_id) REFERENCES public.parc_info_equipements(id) ON DELETE CASCADE;


--
-- Name: parc_info_mobiles parc_info_mobiles_type_mobile_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_mobiles
    ADD CONSTRAINT parc_info_mobiles_type_mobile_id_foreign FOREIGN KEY (type_mobile_id) REFERENCES public.parc_info_types_mobiles(id) ON DELETE SET NULL;


--
-- Name: parc_info_mouvements_consommables parc_info_mouvements_consommables_consommable_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_mouvements_consommables
    ADD CONSTRAINT parc_info_mouvements_consommables_consommable_id_foreign FOREIGN KEY (consommable_id) REFERENCES public.parc_info_consommables(id) ON DELETE CASCADE;


--
-- Name: parc_info_mouvements_consommables parc_info_mouvements_consommables_employe_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_mouvements_consommables
    ADD CONSTRAINT parc_info_mouvements_consommables_employe_id_foreign FOREIGN KEY (employe_id) REFERENCES public.grh_dossiers_employes(id) ON DELETE SET NULL;


--
-- Name: parc_info_mouvements_consommables parc_info_mouvements_consommables_equipement_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_mouvements_consommables
    ADD CONSTRAINT parc_info_mouvements_consommables_equipement_id_foreign FOREIGN KEY (equipement_id) REFERENCES public.parc_info_equipements(id) ON DELETE SET NULL;


--
-- Name: parc_info_mouvements_consommables parc_info_mouvements_consommables_service_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_mouvements_consommables
    ADD CONSTRAINT parc_info_mouvements_consommables_service_id_foreign FOREIGN KEY (service_id) REFERENCES public.organisation_services(id) ON DELETE SET NULL;


--
-- Name: parc_info_mouvements_consommables parc_info_mouvements_consommables_unite_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_mouvements_consommables
    ADD CONSTRAINT parc_info_mouvements_consommables_unite_id_foreign FOREIGN KEY (unite_id) REFERENCES public.organisation_unites(id) ON DELETE SET NULL;


--
-- Name: parc_info_mouvements_consommables parc_info_mouvements_consommables_utilisateur_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_mouvements_consommables
    ADD CONSTRAINT parc_info_mouvements_consommables_utilisateur_id_foreign FOREIGN KEY (utilisateur_id) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: parc_info_ordinateurs parc_info_ordinateurs_cpu_type_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_ordinateurs
    ADD CONSTRAINT parc_info_ordinateurs_cpu_type_id_foreign FOREIGN KEY (cpu_type_id) REFERENCES public.parc_info_types_cpus(id) ON DELETE SET NULL;


--
-- Name: parc_info_ordinateurs parc_info_ordinateurs_disque_type_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_ordinateurs
    ADD CONSTRAINT parc_info_ordinateurs_disque_type_id_foreign FOREIGN KEY (disque_type_id) REFERENCES public.parc_info_types_disques(id) ON DELETE SET NULL;


--
-- Name: parc_info_ordinateurs parc_info_ordinateurs_equipement_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_ordinateurs
    ADD CONSTRAINT parc_info_ordinateurs_equipement_id_foreign FOREIGN KEY (equipement_id) REFERENCES public.parc_info_equipements(id) ON DELETE CASCADE;


--
-- Name: parc_info_ordinateurs parc_info_ordinateurs_os_type_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_ordinateurs
    ADD CONSTRAINT parc_info_ordinateurs_os_type_id_foreign FOREIGN KEY (os_type_id) REFERENCES public.parc_info_types_os(id) ON DELETE SET NULL;


--
-- Name: parc_info_ordinateurs parc_info_ordinateurs_ram_type_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_ordinateurs
    ADD CONSTRAINT parc_info_ordinateurs_ram_type_id_foreign FOREIGN KEY (ram_type_id) REFERENCES public.parc_info_types_rams(id) ON DELETE SET NULL;


--
-- Name: parc_info_scanners parc_info_scanners_equipement_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_scanners
    ADD CONSTRAINT parc_info_scanners_equipement_id_foreign FOREIGN KEY (equipement_id) REFERENCES public.parc_info_equipements(id) ON DELETE CASCADE;


--
-- Name: parc_info_serveurs parc_info_serveurs_cpu_type_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_serveurs
    ADD CONSTRAINT parc_info_serveurs_cpu_type_id_foreign FOREIGN KEY (cpu_type_id) REFERENCES public.parc_info_types_cpus(id) ON DELETE SET NULL;


--
-- Name: parc_info_serveurs parc_info_serveurs_disque_type_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_serveurs
    ADD CONSTRAINT parc_info_serveurs_disque_type_id_foreign FOREIGN KEY (disque_type_id) REFERENCES public.parc_info_types_disques(id) ON DELETE SET NULL;


--
-- Name: parc_info_serveurs parc_info_serveurs_equipement_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_serveurs
    ADD CONSTRAINT parc_info_serveurs_equipement_id_foreign FOREIGN KEY (equipement_id) REFERENCES public.parc_info_equipements(id) ON DELETE CASCADE;


--
-- Name: parc_info_serveurs parc_info_serveurs_os_type_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_serveurs
    ADD CONSTRAINT parc_info_serveurs_os_type_id_foreign FOREIGN KEY (os_type_id) REFERENCES public.parc_info_types_os(id) ON DELETE SET NULL;


--
-- Name: parc_info_serveurs parc_info_serveurs_ram_type_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_serveurs
    ADD CONSTRAINT parc_info_serveurs_ram_type_id_foreign FOREIGN KEY (ram_type_id) REFERENCES public.parc_info_types_rams(id) ON DELETE SET NULL;


--
-- Name: parc_info_serveurs_virtuels parc_info_serveurs_virtuels_cpu_type_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_serveurs_virtuels
    ADD CONSTRAINT parc_info_serveurs_virtuels_cpu_type_id_foreign FOREIGN KEY (cpu_type_id) REFERENCES public.parc_info_types_cpus(id) ON DELETE SET NULL;


--
-- Name: parc_info_serveurs_virtuels parc_info_serveurs_virtuels_disque_type_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_serveurs_virtuels
    ADD CONSTRAINT parc_info_serveurs_virtuels_disque_type_id_foreign FOREIGN KEY (disque_type_id) REFERENCES public.parc_info_types_disques(id) ON DELETE SET NULL;


--
-- Name: parc_info_serveurs_virtuels parc_info_serveurs_virtuels_equipement_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_serveurs_virtuels
    ADD CONSTRAINT parc_info_serveurs_virtuels_equipement_id_foreign FOREIGN KEY (equipement_id) REFERENCES public.parc_info_equipements(id) ON DELETE CASCADE;


--
-- Name: parc_info_serveurs_virtuels parc_info_serveurs_virtuels_os_type_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_serveurs_virtuels
    ADD CONSTRAINT parc_info_serveurs_virtuels_os_type_id_foreign FOREIGN KEY (os_type_id) REFERENCES public.parc_info_types_os(id) ON DELETE SET NULL;


--
-- Name: parc_info_serveurs_virtuels parc_info_serveurs_virtuels_ram_type_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_serveurs_virtuels
    ADD CONSTRAINT parc_info_serveurs_virtuels_ram_type_id_foreign FOREIGN KEY (ram_type_id) REFERENCES public.parc_info_types_rams(id) ON DELETE SET NULL;


--
-- Name: parc_info_serveurs_virtuels parc_info_serveurs_virtuels_serveur_hote_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_serveurs_virtuels
    ADD CONSTRAINT parc_info_serveurs_virtuels_serveur_hote_id_foreign FOREIGN KEY (serveur_hote_id) REFERENCES public.parc_info_serveurs(equipement_id) ON DELETE SET NULL;


--
-- Name: parc_info_telephones parc_info_telephones_equipement_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.parc_info_telephones
    ADD CONSTRAINT parc_info_telephones_equipement_id_foreign FOREIGN KEY (equipement_id) REFERENCES public.parc_info_equipements(id) ON DELETE CASCADE;


--
-- Name: role_has_permissions role_has_permissions_permission_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES public.permissions(id) ON DELETE CASCADE;


--
-- Name: role_has_permissions role_has_permissions_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: users users_dossier_employe_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: parc_info
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_dossier_employe_id_foreign FOREIGN KEY (dossier_employe_id) REFERENCES public.grh_dossiers_employes(id) ON DELETE SET NULL;


--
-- Name: SCHEMA public; Type: ACL; Schema: -; Owner: pg_database_owner
--

GRANT ALL ON SCHEMA public TO parc_info;


--
-- Name: DEFAULT PRIVILEGES FOR SEQUENCES; Type: DEFAULT ACL; Schema: public; Owner: postgres
--

ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public GRANT ALL ON SEQUENCES TO parc_admin;


--
-- Name: DEFAULT PRIVILEGES FOR FUNCTIONS; Type: DEFAULT ACL; Schema: public; Owner: postgres
--

ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public GRANT ALL ON FUNCTIONS TO parc_admin;


--
-- Name: DEFAULT PRIVILEGES FOR TABLES; Type: DEFAULT ACL; Schema: public; Owner: postgres
--

ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public GRANT ALL ON TABLES TO parc_admin;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public GRANT ALL ON TABLES TO parc_info;


--
-- PostgreSQL database dump complete
--

\unrestrict dbXcapETEqMnh861bdNmMfdMUUsPNYKPYwwCWV9SIOjt14kFos4l0GZRL73wyiG

