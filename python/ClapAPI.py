import requests


class ClapAPI:

    def __init__(self, client_id, client_secret, api=None):
        self._api = api or "https://api.clap.video/api/"
        self._max_photos = 5
        self._client_id = client_id
        self._client_secret = client_secret

        self._headers = None
        self._expires_in = None
        self._access_token = None

        self._generate_access_token()

    def _generate_url(self, endpoint):
        return "{}{}".format(self._api, endpoint)

    def _make_request(self, method, url, data=None, headers=None, error=None):
        if not headers:
            headers = self._headers

        url = self._generate_url(url)

        req = requests.request(method, url, json=data, headers=headers)

        if req.status_code not in [requests.codes.ok, requests.codes.created]:
            try:
                detail = req.json().get('detail')
            except ValueError as e:
                detail = None

            raise Exception(error or "{} Error while making a Clap Request".format(req.status_code), detail or req.text)

        return req.json()

    def _generate_access_token(self):
        req = self._make_request("POST", "oauth",
                                 data={"client_id": self._client_id, "client_secret": self._client_secret},
                                 error="Error while generating Access Token")

        self._access_token = req.get('access_token')
        self._expires_in = req.get('expires_in', 0)
        self._headers = {"Authorization": "Access-Token {}".format(self._access_token)}

    def get_project(self, id):
        return self._make_request("GET", "video/projects/{}".format(id))

    def create_project(self, **kwargs):
        json_fields = kwargs.get('json_fields', {})

        if kwargs.get('webhook_infos'):
            json_fields['webhook_infos'] = kwargs.get('webhook_infos')
            del kwargs['webhook_infos']
        if kwargs.get('realty'):
            json_fields['realty'] = kwargs.get('realty')
            del kwargs['realty']

        photos_list = kwargs.get('photos', [])

        if photos_list and type(photos_list) is list:
            if not json_fields.get('realty'):
                json_fields['realty'] = {}

            i = 1
            for photo in photos_list:
                json_fields['realty']['photo{}'.format(i)] = photo

                if i < self._max_photos:
                    i += 1
                else:
                    break
            del kwargs["photos"]

        kwargs['json_fields'] = json_fields

        return self._make_request("POST", "video/projects", data=kwargs)

    def get_user_token(self, user_id):
        req = self._make_request("POST", "users/{}/get-auth-token".format(user_id))

        return req.get('token')
