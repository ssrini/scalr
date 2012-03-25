<?
	final class CLIENT_SETTINGS
	{
		const MAX_INSTANCES_LIMIT 	= 'client_max_instances';
		const MAX_EIPS_LIMIT 		= 'client_max_eips';
		const RSS_LOGIN 			= 'rss_login';
		const RSS_PASSWORD 			= 'rss_password';
		const SYNC_TIMEOUT			= 'sync_timeout';

		const TIMEZONE				= 'system.timezone';

		const BILLING_PACKAGE		= 'billing.packageid';

		const AD_PAGES_VISITED 			= 'adwords.pages_visited';
		const AD_VALUE_TRACK 			= 'adwords.value_track';
		const AD_COMPAIGN				= 'ad.compaign';

		const BILLING_CGF_CID		= 'billing.chargify.customer_id';
		const BILLING_CGF_SID		= 'billing.chargify.subscription_id';
		const BILLING_CGF_PKG		= 'billing.chargify.package_id';

		/**** Google Analytics ****/

		const GA_FIRST_VISIT		= 'ga.first_visit';
		const GA_PREVIOUS_VISIT		= 'ga.previous_visit';
		const GA_TIMES_VISITED		= 'ga.times_visited';

		const GA_CAMPAIGN_SOURCE	= 'ga.utm_source';
		const GA_CAMPAIGN_NAME		= 'ga.utm_campaign';
		const GA_CAMPAIGN_MEDIUM	= 'ga.utm_medium';
		const GA_CAMPAIGN_TERM		= 'ga.utm_term';
		const GA_CAMPAIGN_CONTENT	= 'ga.utm_content';

		/*** Salesforce info **/

		const DATE_FIRST_LOGIN		= 'date.first_login';
		const DATE_ENV_CONFIGURED	= 'date.env_configured';
		const DATE_FARM_CREATED		= 'date.farm_created';
	}
