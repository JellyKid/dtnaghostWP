<?php
class DtnAgHostSettings
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'Settings Admin', 
            'DTN AgHost', 
            'manage_options', 
            'dtn-aghost-settings', 
            array( $this, 'create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option( 'dtn_option_name' );
        ?>
        <div class="wrap">
            <h2>DTN Aghost Integrator Settings</h2> 
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'dtn_option_group' );   
                do_settings_sections( 'dtn-setting-admin' );
                submit_button(); 
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {        
        register_setting(
            'dtn_option_group', // Option group
            'dtn_option_name', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'dtn_userpass_settings', // ID
            'Username and Password', // Title
            array( $this, 'print_user_section' ), // Callback
            'dtn-setting-admin' // Page
        );  
		
		add_settings_section(
            'dtn_location_settings', // ID
            'Location', // Title
            array( $this, 'print_location_section' ), // Callback
            'dtn-setting-admin' // Page
        );  
		
		add_settings_section(
            'dtn_current_settings', // ID
            'Current Conditions', // Title
            array( $this, 'print_current_section' ), // Callback
            'dtn-setting-admin' // Page
        );  

        add_settings_field(
            'dtn_user', // ID
            'Username', // Title 
            array( $this, 'dtn_user_callback' ), // Callback
            'dtn-setting-admin', // Page
            'dtn_userpass_settings' // Section           
        );      

        add_settings_field(
            'dtn_password', 
            'Password', 
            array( $this, 'dtn_password_callback' ), 
            'dtn-setting-admin', 
            'dtn_userpass_settings'
        );
		
		add_settings_field(
            'dtn_zip', // ID
            'Zip Code', // Title 
            array( $this, 'dtn_zip_callback' ), // Callback
            'dtn-setting-admin', // Page
            'dtn_location_settings' // Section           
        );  
		
		add_settings_field(
            'dtn_lat', // ID
            'Latitude', // Title 
            array( $this, 'dtn_lat_callback' ), // Callback
            'dtn-setting-admin', // Page
            'dtn_location_settings' // Section           
        ); 
		
		add_settings_field(
            'dtn_long', // ID
            'Longitude', // Title 
            array( $this, 'dtn_long_callback' ), // Callback
            'dtn-setting-admin', // Page
            'dtn_location_settings' // Section           
        );
		
		add_settings_field(
            'dtn_radarpopup', // ID
            'Include Mini Radar', // Title 
            array( $this, 'dtn_radarpopup_callback' ), // Callback
            'dtn-setting-admin', // Page
            'dtn_current_settings' // Section           
        ); 
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
        $new_input = array();

        if( isset( $input['dtn_user'] ) )
            $new_input['dtn_user'] = sanitize_text_field( $input['dtn_user'] );
		
		if( isset( $input['dtn_password'] ) )
            $new_input['dtn_password'] = sanitize_text_field( $input['dtn_password'] );
		
		if( isset( $input['dtn_zip'] ) )
            $new_input['dtn_zip'] = sanitize_text_field( $input['dtn_zip'] );
		
		if( isset( $input['dtn_lat'] ) )
            $new_input['dtn_lat'] = sanitize_text_field( $input['dtn_lat'] );
		
		if( isset( $input['dtn_long'] ) )
            $new_input['dtn_long'] = sanitize_text_field( $input['dtn_long'] );
		
		if( isset( $input['dtn_radarpopup'] ) )
            $new_input['dtn_radarpopup'] = $input['dtn_long'];

        return $new_input;
    }

    /** 
     * Print the Section text
     */
    public function print_user_section()
    {
        print 'Enter your username and password you received from DTN below:';
    }
	
	public function print_location_section()
    {
        print 'Put in your zip code for weather and Latitude/Longitude for radar:';
    }
	
	public function print_current_section()
    {
        print 'Customize Current Conditions section:';
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function dtn_user_callback()
    {
        printf(
            '<input type="text" id="dtn_user" name="dtn_option_name[dtn_user]" value="%s" />',
            isset( $this->options['dtn_user'] ) ? esc_attr( $this->options['dtn_user']) : ''
        );
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function dtn_password_callback()
    {
        printf(
            '<input type="text" id="dtn_password" name="dtn_option_name[dtn_password]" value="%s" />',
            isset( $this->options['dtn_password'] ) ? esc_attr( $this->options['dtn_password']) : ''
        );
    }
	
	public function dtn_zip_callback()
    {
        printf(
            '<input type="text" id="dtn_zip" name="dtn_option_name[dtn_zip]" maxlength="5" value="%s" />',
            empty( $this->options['dtn_zip'] ) ? '48413' : esc_attr( $this->options['dtn_zip'])
        );
    }
	
	public function dtn_lat_callback()
    {
        printf(
            '<input type="text" id="dtn_lat" name="dtn_option_name[dtn_lat]" maxlength="9" value="%s" />',
            empty( $this->options['dtn_lat'] ) ? '43.8312' : esc_attr( $this->options['dtn_lat'])
        );
    }
	
	public function dtn_long_callback()
    {
        printf(
            '<input type="text" id="dtn_long" name="dtn_option_name[dtn_long]" value="%s" />',
            empty( $this->options['dtn_long'] ) ? '-83.2728' : esc_attr( $this->options['dtn_long'])
        );
    }
	
	public function dtn_radarpopup_callback()
    {
        printf(
            '<input type="checkbox" id="dtn_radarpopup" name="dtn_option_name[dtn_radarpopup]" %s value="true" />',
            isset( $this->options['dtn_radarpopup'] ) ? "checked" : "unchecked"
        );
    }
}

if( is_admin() )
    $my_settings_page = new DtnAgHostSettings();
?>